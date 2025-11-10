<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentGatewaysController extends Controller
{
    /**
     * GET /api/admin/payment-gateways
     * Query params:
     *   - type: razorpay|upi|cash (optional; if omitted returns all types merged)
     *   - active: 1|0 (optional)
     */
    public function index(Request $request)
    {
        $type   = $request->query('type');        // razorpay|upi|cash|null
        $active = $request->query('active', null);

        $fetch = function (string $t) use ($active) {
            $table = $this->tableForType($t);
            $q = DB::table($table)->whereNull('deleted_at');
            if (!is_null($active)) {
                $q->where('is_active', (int)filter_var($active, FILTER_VALIDATE_BOOLEAN));
            }
            return $q->select(
                DB::raw("'{$t}' as type"),
                "{$table}.id",
                "{$table}.code",
                "{$table}.display_name",
                "{$table}.is_active",
                "{$table}.is_default",
                // provider-specific previews (null for others)
                $t === 'razorpay' ? "{$table}.key_id as preview_1" : DB::raw("NULL as preview_1"),
                $t === 'upi'      ? "{$table}.vpa as preview_1"    : DB::raw("NULL as preview_1"),
            )
            ->orderBy('is_default','desc')
            ->orderBy('display_name')
            ->get();
        };

        if ($type) {
            return response()->json(['status'=>'success','data'=>$fetch($type)]);
        }

        $data = $fetch('razorpay')->merge($fetch('upi'))->merge($fetch('cash'))->values();
        return response()->json(['status'=>'success','data'=>$data]);
    }

    /**
     * GET /api/admin/payment-gateways/{type}/{id}
     */
    public function show(string $type, int $id)
    {
        $table = $this->tableForType($type);
        $gw = DB::table($table)->where('id', $id)->whereNull('deleted_at')->first();
        if (!$gw) return response()->json(['status'=>'error','message'=>'Not found'], 404);
        return response()->json(['status'=>'success','data'=>$gw]);
    }

    /**
     * POST /api/admin/payment-gateways
     * Body:
     *   type: razorpay|upi|cash
     *   code, display_name, is_active?, is_default?
     *   razorpay: key_id, key_secret, webhook_secret, credentials?
     *   upi: vpa, merchant_name?, qr_code_path?, deeplink_base?, credentials?
     *   cash: (no extra)
     */
    public function store(Request $request)
    {
        $type = $request->input('type');
        $this->validateType($type);

        $rules = $this->rulesFor($type, 'store');
        $payload = $request->validate($rules);

        if (!empty($payload['is_default'])) {
            $this->unsetDefaults($type);
        }

        $table = $this->tableForType($type);
        $row   = $this->mapPayloadToRow($type, $payload);

        $id = DB::table($table)->insertGetId($row + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = DB::table($table)->where('id', $id)->first();
        return response()->json(['status'=>'success','data'=>$data], 201);
    }

    /**
     * PUT /api/admin/payment-gateways/{type}/{id}
     * Note: type is immutable (path param must match existing row’s type).
     */
    public function update(Request $request, string $type, int $id)
    {
        $this->validateType($type);
        $table = $this->tableForType($type);

        $existing = DB::table($table)->where('id', $id)->whereNull('deleted_at')->first();
        if (!$existing) return response()->json(['status'=>'error','message'=>'Not found'], 404);

        $rules = $this->rulesFor($type, 'update', $id);
        $payload = $request->validate($rules);

        if (array_key_exists('is_default', $payload) && $this->toBool($payload['is_default'])) {
            $this->unsetDefaults($type);
        }

        $row = $this->mapPayloadToRow($type, array_merge((array)$existing, $payload), true);

        DB::table($table)->where('id', $id)->update($row + [
            'updated_at' => now(),
        ]);

        $data = DB::table($table)->where('id', $id)->first();
        return response()->json(['status'=>'success','data'=>$data]);
    }

    /**
     * PATCH /api/admin/payment-gateways/{type}/{id}/activate
     * Body: { is_active: 1|0 }
     */
    public function activate(Request $request, string $type, int $id)
    {
        $this->validateType($type);
        $table = $this->tableForType($type);

        $request->validate(['is_active' => ['required', Rule::in([0,1,'0','1',true,false])]]);
        $exists = DB::table($table)->where('id', $id)->whereNull('deleted_at')->exists();
        if (!$exists) return response()->json(['status'=>'error','message'=>'Not found'], 404);

        DB::table($table)->where('id', $id)->update([
            'is_active' => (int)$this->toBool($request->input('is_active')),
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success']);
    }

    /**
     * PATCH /api/admin/payment-gateways/{type}/{id}/default
     * Make this gateway default within its type.
     */
    public function makeDefault(string $type, int $id)
    {
        $this->validateType($type);
        $table = $this->tableForType($type);

        $gw = DB::table($table)->where('id', $id)->whereNull('deleted_at')->first();
        if (!$gw) return response()->json(['status'=>'error','message'=>'Not found'], 404);

        $this->unsetDefaults($type);
        DB::table($table)->where('id', $id)->update([
            'is_default' => 1,
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success']);
    }

    /**
     * DELETE /api/admin/payment-gateways/{type}/{id}
     */
    public function destroy(string $type, int $id)
    {
        $this->validateType($type);
        $table = $this->tableForType($type);

        $exists = DB::table($table)->where('id', $id)->whereNull('deleted_at')->exists();
        if (!$exists) return response()->json(['status'=>'error','message'=>'Not found'], 404);

        DB::table($table)->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status'=>'success']);
    }

    /* ==========================
       Helpers
       ========================== */

    protected function validateType(?string $type): void
    {
        if (!in_array($type, ['razorpay','upi','cash'], true)) {
            abort(response()->json(['status'=>'error','message'=>'Invalid type'], 422));
        }
    }

    protected function tableForType(string $type): string
    {
        return match ($type) {
            'razorpay' => 'razorpay_gateways',
            'upi'      => 'upi_gateways',
            'cash'     => 'cash_gateways',
        };
    }

    protected function rulesFor(string $type, string $mode = 'store', ?int $id = null): array
    {
        $uniqueCode = Rule::unique($this->tableForType($type), 'code');
        if ($mode === 'update' && $id) $uniqueCode = $uniqueCode->ignore($id);

        $base = [
            'code'         => [$mode === 'store' ? 'required' : 'sometimes', 'string', 'max:64', $uniqueCode],
            'display_name' => [$mode === 'store' ? 'required' : 'sometimes', 'string', 'max:128'],
            'is_active'    => ['sometimes', Rule::in([0,1,'0','1',true,false])],
            'is_default'   => ['sometimes', Rule::in([0,1,'0','1',true,false])],
            'credentials'  => ['sometimes','array'],
        ];

        if ($type === 'razorpay') {
            $extra = [
                'key_id'         => [$mode === 'store' ? 'required' : 'sometimes', 'string', 'max:191'],
                'key_secret'     => [$mode === 'store' ? 'required' : 'sometimes', 'string', 'max:191'],
                'webhook_secret' => [$mode === 'store' ? 'required' : 'sometimes', 'string', 'max:191'],
            ];
        } elseif ($type === 'upi') {
            $extra = [
                'vpa'            => [$mode === 'store' ? 'required' : 'sometimes', 'string', 'max:191'],
                'merchant_name'  => ['sometimes','nullable','string','max:191'],
                'qr_code_path'   => ['sometimes','nullable','string','max:255'],
                'deeplink_base'  => ['sometimes','nullable','string','max:500'],
            ];
        } else { // cash
            $extra = [];
        }

        return array_merge($base, $extra);
    }

    protected function toBool($val): bool
    {
        return filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Remove default flag from all gateways of a given type.
     */
    protected function unsetDefaults(string $type): void
    {
        DB::table($this->tableForType($type))->update(['is_default' => 0]);
    }

    /**
     * Map validated payload to DB columns for each table.
     * $isUpdate = true keeps unspecified fields untouched by not setting them.
     */
    protected function mapPayloadToRow(string $type, array $p, bool $isUpdate = false): array
    {
        $row = [];

        foreach (['code','display_name'] as $k) {
            if ($isUpdate && !array_key_exists($k, $p)) continue;
            if (array_key_exists($k, $p)) $row[$k] = $p[$k];
        }

        if (array_key_exists('is_active', $p))  $row['is_active']  = (int)$this->toBool($p['is_active']);
        if (array_key_exists('is_default', $p)) $row['is_default'] = (int)$this->toBool($p['is_default']);

        if ($type === 'razorpay') {
            foreach (['key_id','key_secret','webhook_secret'] as $k) {
                if ($isUpdate && !array_key_exists($k, $p)) continue;
                if (array_key_exists($k, $p)) $row[$k] = $p[$k];
            }
        } elseif ($type === 'upi') {
            foreach (['vpa','merchant_name','qr_code_path','deeplink_base'] as $k) {
                if ($isUpdate && !array_key_exists($k, $p)) continue;
                if (array_key_exists($k, $p)) $row[$k] = $p[$k];
            }
        } // cash has no extra columns

        // optional JSON credentials passthrough
        if (array_key_exists('credentials', $p)) {
            $row['credentials'] = json_encode($p['credentials']);
        }

        return $row;
    }
}
