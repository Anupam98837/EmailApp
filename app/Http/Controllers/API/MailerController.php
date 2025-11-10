<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class MailerController extends Controller
{
    /**
     * Extract authenticated user ID from Bearer token.
     */
    private function getAuthenticatedUserId(Request $request): int
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s(\S+)/', $header, $m)) {
            abort(response()->json(['status' => 'error', 'message' => 'Token not provided'], 401));
        }

        $record = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $m[1]))
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();

        if (!$record) {
            abort(response()->json(['status' => 'error', 'message' => 'Invalid token'], 401));
        }

        return (int) $record->tokenable_id;
    }

    /**
     * Normalise incoming keys (camelCase or snake_case) into camelCase.
     */
    private function normalizeKeys(Request $request): void
    {
        $request->merge([
            'fromAddress' => $request->input('fromAddress', $request->input('from_address')),
            'fromName'    => $request->input('fromName',    $request->input('from_name')),
            'isDefault'   => $request->input('isDefault',   $request->input('is_default')),
        ]);
    }

    private function rules(): array
    {
        return [
            'mailer'      => 'required|string',
            'host'        => 'required|string',
            'port'        => 'required|integer',
            'username'    => 'required|string',
            'password'    => 'required|string',
            'encryption'  => 'nullable|string',
            'fromAddress' => 'required|email',
            'fromName'    => 'required|string',
            'isDefault'   => 'sometimes|nullable|boolean',
        ];
    }

    /**
     * Force from_address to be acceptable for the SMTP server.
     * Simplest: make it identical to username if username is an email.
     * Change to domain-only enforcement if needed.
     */
    private function enforceFromMatchesUsername(array &$payload): void
    {
        if (!empty($payload['username']) && filter_var($payload['username'], FILTER_VALIDATE_EMAIL)) {
            $payload['from_address'] = $payload['username'];
        }
        // Example for domain-only check instead:
        // $userDomain = substr(strrchr($payload['username'], '@'), 1);
        // $fromDomain = substr(strrchr($payload['from_address'], '@'), 1);
        // if (strcasecmp($userDomain, $fromDomain) !== 0) {
        //     $payload['from_address'] = $payload['username'];
        // }
    }

    private function buildPayload(Request $request, int $userId, bool $forUpdate = false, ?bool $forceDefault = null): array
    {
        $base = [
            'mailer'       => $request->mailer,
            'host'         => $request->host,
            'port'         => $request->port,
            'username'     => $request->username,
            'password'     => $request->password,
            'encryption'   => $request->encryption,
            'from_address' => $request->fromAddress,
            'from_name'    => $request->fromName,
        ];

        if ($forUpdate) {
            $base['updated_at'] = now();
        } else {
            $base += [
                'user_id'    => $userId,
                'is_default' => $forceDefault === true ? 1 : (int) $request->boolean('isDefault', false),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $base;
    }

    /**
     * GET /api/mailer
     */
    /**
 * GET /api/mailer
 */
public function index(Request $request)
{
    $userId = $this->getAuthenticatedUserId($request);
    // Log::info('Listing mailer settings', ['user_id' => $userId]);

    // Fetch user's subscription plan (if any) and resolve its admin mailer templates
    $planMailerTemplates = [];
    $user = DB::table('users')->where('id', $userId)->first();
    if ($user && ! empty($user->subscription_plan_id)) {
        $plan = DB::table('subscription_plans')->where('id', $user->subscription_plan_id)->first();
        if ($plan) {
            $mailerAdminIds = [];
            if (!empty($plan->mailer_settings_admin_ids)) {
                $decoded = json_decode($plan->mailer_settings_admin_ids, true);
                if (is_array($decoded)) {
                    $mailerAdminIds = array_values(array_filter($decoded, fn($v) => is_numeric($v)));
                }
            } elseif (!empty($plan->mailer_settings_admin_id)) {
                $mailerAdminIds = [$plan->mailer_settings_admin_id];
            }

            if (!empty($mailerAdminIds)) {
                $planMailerTemplates = DB::table('mailer_settings_admin')
                    ->whereIn('id', $mailerAdminIds)
                    ->get()
                    ->map(function ($m) {
                        return [
                            'mailer'       => $m->mailer,
                            'host'         => $m->host,
                            'port'         => $m->port,
                            'username'     => $m->username,
                            'encryption'   => $m->encryption,
                            'from_address' => $m->from_address,
                            'from_name'    => $m->from_name,
                        ];
                    })
                    ->toArray();
                // Log::info('Resolved plan-derived mailer templates for action flag', [
                //     'user_id' => $userId,
                //     'template_count' => count($planMailerTemplates),
                // ]);
            }
        }
    }

    $list = DB::table('mailer_settings')
        ->where('user_id', $userId)
        ->orderByDesc('is_default')
        ->get()
        ->map(function ($m) use ($planMailerTemplates) {
            // Determine if this setting matches any plan-derived template
            $isFromPlan = false;
            foreach ($planMailerTemplates as $tpl) {
                if (
                    strcasecmp($m->mailer, $tpl['mailer']) === 0 &&
                    strcasecmp($m->host, $tpl['host']) === 0 &&
                    intval($m->port) === intval($tpl['port']) &&
                    strcasecmp($m->username, $tpl['username']) === 0 &&
                    (
                        (is_null($tpl['encryption']) && empty($m->encryption)) ||
                        ( ! is_null($tpl['encryption']) && strcasecmp($m->encryption, $tpl['encryption']) === 0)
                    ) &&
                    strcasecmp($m->from_address, $tpl['from_address']) === 0 &&
                    strcasecmp($m->from_name, $tpl['from_name']) === 0
                ) {
                    $isFromPlan = true;
                    break;
                }
            }

            $record = (array) $m;
            // action: false if from plan (i.e., restricted), true otherwise
            $record['action'] = $isFromPlan ? false : true;
            return $record;
        });

    return response()->json([
        'status'  => 'success',
        'message' => 'Mailer settings list retrieved.',
        'data'    => $list,
    ], 200);
}


    /**
     * POST /api/mailer
     */
    public function store(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        $this->normalizeKeys($request);

        // Log::info('Creating mailer setting', ['user_id' => $userId, 'payload' => $request->all()]);

        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            Log::warning('Validation failed (create mailer)', ['errors' => $v->errors()->all()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $forceDefault = DB::table('mailer_settings')->where('user_id', $userId)->count() === 0;

        $payload = $this->buildPayload($request, $userId, false, $forceDefault);
        $this->enforceFromMatchesUsername($payload);

        try {
            DB::beginTransaction();

            if ($payload['is_default']) {
                DB::table('mailer_settings')
                    ->where('user_id', $userId)
                    ->update(['is_default' => 0]);
            }

            $newId = DB::table('mailer_settings')->insertGetId($payload);
            DB::commit();

            $setting = DB::table('mailer_settings')->where('id', $newId)->first();
            // Log::info('Mailer setting created', ['id' => $newId]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Mailer setting created.',
                'data'    => $setting,
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            // Log::error('Error creating mailer setting', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not create mailer setting.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/mailer/{id}
     */
    public function show(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Fetching mailer setting', ['user_id' => $userId, 'id' => $id]);

        $setting = DB::table('mailer_settings')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$setting) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mailer setting not found.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Mailer setting retrieved.',
            'data'    => $setting,
        ], 200);
    }

    /**
     * PUT /api/mailer/{id}
     */
    public function update(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        $this->normalizeKeys($request);

        // Log::info('Updating mailer setting', ['user_id' => $userId, 'id' => $id, 'payload' => $request->all()]);

        $exists = DB::table('mailer_settings')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->exists();

        if (!$exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mailer setting not found.',
            ], 404);
        }

        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            // Log::warning('Validation failed (update mailer)', ['errors' => $v->errors()->all()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $v->errors(),
            ], 422);
        }

        $payload     = $this->buildPayload($request, $userId, true);
        $this->enforceFromMatchesUsername($payload);

        $makeDefault = $request->boolean('isDefault', false);

        try {
            DB::beginTransaction();

            if ($makeDefault) {
                DB::table('mailer_settings')
                    ->where('user_id', $userId)
                    ->update(['is_default' => 0]);

                $payload['is_default'] = 1;
            }

            DB::table('mailer_settings')
                ->where('id', $id)
                ->update($payload);

            DB::commit();

            $setting = DB::table('mailer_settings')->where('id', $id)->first();
            // Log::info('Mailer setting updated', ['id' => $id]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Mailer setting updated.',
                'data'    => $setting,
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error updating mailer setting', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not update mailer setting.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/mailer/{id}/default
     */
    public function setDefault(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Setting mailer default', ['user_id' => $userId, 'id' => $id]);

        $setting = DB::table('mailer_settings')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$setting) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mailer setting not found.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($userId, $id) {
                DB::table('mailer_settings')
                    ->where('user_id', $userId)
                    ->update(['is_default' => 0]);

                DB::table('mailer_settings')
                    ->where('id', $id)
                    ->update(['is_default' => 1, 'updated_at' => now()]);
            });

            $setting = DB::table('mailer_settings')->where('id', $id)->first();
            // Log::info('Mailer setting marked as default', ['id' => $id]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Mailer setting marked as default.',
                'data'    => $setting,
            ], 200);

        } catch (Throwable $e) {
            // Log::error('Error setting default mailer', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not set default.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/mailer/{id}
     */
    public function destroy(Request $request, $id)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Deleting mailer setting', ['user_id' => $userId, 'id' => $id]);

        $deleted = DB::table('mailer_settings')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        if ($deleted) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Mailer setting deleted.',
            ], 200);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Mailer setting not found.',
        ], 404);
    }

    /**
     * POST /api/mailer/clear-defaults
     */
    public function clearDefaults(Request $request)
    {
        $userId = $this->getAuthenticatedUserId($request);
        // Log::info('Clearing all user-default flags', ['user_id' => $userId]);

        try {
            DB::table('mailer_settings')
                ->where('user_id', $userId)
                ->update(['is_default' => 0, 'updated_at' => now()]);

            return response()->json([
                'status'  => 'success',
                'message' => 'All mailer settings cleared of default flag.',
            ], 200);
        } catch (Throwable $e) {
            // Log::error('Error clearing defaults', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not clear defaults.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
