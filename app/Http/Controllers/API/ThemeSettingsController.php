<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThemeSettingsController extends Controller
{
    /* =========================================================
     |  THEME MASTER (themes) — CRUD
     * ========================================================= */

    /** GET /api/themes */
    public function themesIndex(Request $request)
    {
        $themes = DB::table('themes')->orderByDesc('updated_at')->get();
        return response()->json(['status' => 'success', 'data' => $themes], 200);
    }

    /** GET /api/themes/{id} */
    public function themesShow(Request $request, int $id)
    {
        $theme = DB::table('themes')->where('id', $id)->first();
        if (! $theme) {
            return response()->json(['status' => 'error', 'message' => 'Theme not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $theme], 200);
    }

    /** POST /api/themes */
    public function themesStore(Request $request)
    {
        $data = $this->validateThemePayload($request);

        $payload = array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id  = DB::table('themes')->insertGetId($payload);
        $row = DB::table('themes')->where('id', $id)->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'Theme created',
            'data'    => $row,
        ], 201);
    }

    /** PUT /api/themes/{id} */
    public function themesUpdate(Request $request, int $id)
    {
        if (! DB::table('themes')->where('id', $id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Theme not found'], 404);
        }

        $data = $this->validateThemePayload($request);
        $payload = array_merge($data, ['updated_at' => now()]);

        DB::table('themes')->where('id', $id)->update($payload);
        $row = DB::table('themes')->where('id', $id)->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'Theme updated',
            'data'    => $row,
        ], 200);
    }

    /** DELETE /api/themes/{id} */
    public function themesDestroy(Request $request, int $id)
    {
        $theme = DB::table('themes')->where('id', $id)->first();
        if (! $theme) {
            return response()->json(['status' => 'error', 'message' => 'Theme not found'], 404);
        }

        // Optional safety: block deletion if assigned
        if (DB::table('user_themes')->where('theme_id', $id)->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Theme is assigned to one or more users. Unassign before deleting.'
            ], 422);
        }

        DB::table('themes')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Theme deleted'], 200);
    }


    /* =========================================================
     |  USER ↔ THEME (user_themes) — ASSIGN / UPDATE / DELETE / SHOW
     * ========================================================= */

    /** GET /api/users/{userId}/theme → current mapping + joined theme */
    public function userThemeShow(Request $request, int $userId)
    {
        if (! $this->userExists($userId)) {
            return response()->json(['status'=>'error','message'=>'Invalid user'], 422);
        }

        $map = DB::table('user_themes')->where('user_id', $userId)->first();
        if (! $map) {
            return response()->json(['status'=>'success','data'=>null], 200);
        }

        $theme = DB::table('themes')->where('id', $map->theme_id)->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'mapping' => $map,
                'theme'   => $theme,
            ],
        ], 200);
    }

    /**
     * POST /api/users/{userId}/theme
     * body: { theme_id: int, status?: 'active'|'inactive' }
     * Acts like assign or replace (unique user_id in user_themes recommended).
     */
    public function userThemeAssign(Request $request, int $userId)
    {
        if (! $this->userExists($userId)) {
            return response()->json(['status'=>'error','message'=>'Invalid user'], 422);
        }

        $data = $request->validate([
            'theme_id' => 'required|integer|exists:themes,id',
            'status'   => 'sometimes|in:active,inactive',
        ]);

        $status = $data['status'] ?? 'active';
        $now    = now();

        $existing = DB::table('user_themes')->where('user_id', $userId)->first();
        if ($existing) {
            DB::table('user_themes')
                ->where('user_id', $userId)
                ->update([
                    'theme_id'   => $data['theme_id'],
                    'status'     => $status,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('user_themes')->insert([
                'user_id'    => $userId,
                'theme_id'   => $data['theme_id'],
                'status'     => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $out   = DB::table('user_themes')->where('user_id', $userId)->first();
        $theme = DB::table('themes')->where('id', $out->theme_id)->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'Theme assigned to user',
            'data'    => ['mapping' => $out, 'theme' => $theme],
        ], 200);
    }

    /** PUT /api/users/{userId}/theme — change theme_id and/or status */
    public function userThemeUpdate(Request $request, int $userId)
    {
        if (! $this->userExists($userId)) {
            return response()->json(['status'=>'error','message'=>'Invalid user'], 422);
        }

        $map = DB::table('user_themes')->where('user_id', $userId)->first();
        if (! $map) {
            return response()->json(['status'=>'error','message'=>'No theme assigned to this user'], 404);
        }

        $data = $request->validate([
            'theme_id' => 'sometimes|integer|exists:themes,id',
            'status'   => 'sometimes|in:active,inactive',
        ]);

        $payload = ['updated_at' => now()];
        if (array_key_exists('theme_id', $data)) $payload['theme_id'] = $data['theme_id'];
        if (array_key_exists('status', $data))   $payload['status']   = $data['status'];

        DB::table('user_themes')->where('user_id', $userId)->update($payload);

        $out   = DB::table('user_themes')->where('user_id', $userId)->first();
        $theme = DB::table('themes')->where('id', $out->theme_id)->first();

        return response()->json([
            'status'  => 'success',
            'message' => 'User theme updated',
            'data'    => ['mapping' => $out, 'theme' => $theme],
        ], 200);
    }

    /** DELETE /api/users/{userId}/theme → unassign */
    public function userThemeDelete(Request $request, int $userId)
    {
        if (! $this->userExists($userId)) {
            return response()->json(['status'=>'error','message'=>'Invalid user'], 422);
        }

        $map = DB::table('user_themes')->where('user_id', $userId)->first();
        if (! $map) {
            return response()->json(['status'=>'error','message'=>'No theme assigned to this user'], 404);
        }

        DB::table('user_themes')->where('user_id', $userId)->delete();

        return response()->json(['status'=>'success','message'=>'Theme unassigned from user'], 200);
    }


    /* =========================================================
     |  IMAGE UPLOAD/LIST — /public/assets/media/web_assets/logo
     * ========================================================= */

    /**
     * POST /api/themes/upload
     * form-data: file=<FILE>, kind=logo|custom
     * Saves into: public/assets/media/web_assets/logo
     * Returns: { url: "..." }
     */
    public function uploadAsset(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:png,jpg,jpeg,webp,svg,ico|max:5120',
            'kind' => 'required|in:logo,custom',
        ]);

        $file = $request->file('file');

        $targetDir = $this->logoDirPath(); // ensure folder exists
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $ext  = strtolower($file->getClientOriginalExtension());
        $name = $request->input('kind') . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $file->move($targetDir, $name);

        $url = $this->logoPublicUrl($name);

        return response()->json([
            'status'  => 'success',
            'message' => 'File uploaded',
            'url'     => $url,
        ], 201);
    }

    /**
     * GET /api/themes/logos
     * Returns all logo URLs found on disk (logo folder) and any logo_url saved in themes.
     * { status, data: { disk: [...], db: [...], all: [...] } }
     */
    public function logosList(Request $request)
    {
        // 1) Disk logos
        $dir = $this->logoDirPath();
        $patterns = ['*.png','*.jpg','*.jpeg','*.webp','*.svg','*.ico'];
        $files = [];
        foreach ($patterns as $p) {
            $files = array_merge($files, glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $p) ?: []);
        }
        $disk = array_values(array_map(function ($absPath) {
            return $this->logoPublicUrl(basename($absPath));
        }, $files));

        // 2) DB logos (unique, non-null/non-empty)
        $db = DB::table('themes')
            ->whereNotNull('logo_url')
            ->pluck('logo_url')
            ->filter(fn($u) => trim((string)$u) !== '')
            ->unique()
            ->values()
            ->toArray();

        // 3) union
        $all = array_values(array_unique(array_merge($disk, $db)));

        return response()->json([
            'status' => 'success',
            'data'   => [
                'disk' => $disk,
                'db'   => $db,
                'all'  => $all,
            ],
        ], 200);
    }


    /* =========================================================
     |  HELPERS
     * ========================================================= */

    private function userExists(int $userId): bool
    {
        return DB::table('users')->where('id', $userId)->exists();
    }

    /** Validate theme fields — NO regex, just lightweight string checks */
    private function validateThemePayload(Request $request): array
    {
        // If you want zero restrictions for colors, change $colorRule to: 'sometimes|nullable|string'
        $colorRule = 'sometimes|nullable|string|starts_with:#|min:4|max:7';

        return $request->validate([
            'name'             => 'required|string|max:255',

            'app_name'         => 'sometimes|nullable|string|max:255',
            'logo_url'         => 'sometimes|nullable|string|max:2048',

            'primary_color'    => $colorRule,
            'secondary_color'  => $colorRule,
            'accent_color'     => $colorRule,
            'light_color'      => $colorRule,
            'border_color'     => $colorRule,
            'text_color'       => $colorRule,
            'bg_body'          => $colorRule,

            'info_color'       => $colorRule,
            'success_color'    => $colorRule,
            'warning_color'    => $colorRule,
            'danger_color'     => $colorRule,

            'font_sans'        => 'sometimes|nullable|string|max:255',
            'font_head'        => 'sometimes|nullable|string|max:255',
        ]);
    }

    /** Absolute path for the logo folder */
    private function logoDirPath(): string
    {
        return public_path('assets/media/web_assets/logo');
    }

    /** Public URL for a file stored inside the logo folder */
    private function logoPublicUrl(string $filename): string
    {
        $filename = ltrim($filename, '/\\'); // sanitize
        return asset('assets/media/web_assets/logo/' . $filename);
    }
    // inside App\Http\Controllers\API\ThemeSettingsController

    /**
     * GET /api/my-theme
     * Reads the Bearer token, resolves the authenticated user,
     * and returns their ACTIVE user_themes mapping with the joined theme.
     *
     * Response (200):
     *  { status: "success", data: { mapping, theme } }  // when assigned
     *  { status: "success", data: null }                // when not assigned
     *
     * Response (401) for missing/invalid token.
     */
    public function myTheme(Request $request)
    {
        $userId = $this->tokenUserId($request);
        if (!$userId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: missing or invalid token'
            ], 401);
        }

        // active mapping for this user
        $map = DB::table('user_themes')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$map) {
            return response()->json([
                'status' => 'success',
                'data'   => null,      // no theme assigned
            ], 200);
        }

        $theme = DB::table('themes')->where('id', $map->theme_id)->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'mapping' => $map,
                'theme'   => $theme,
            ],
        ], 200);
    }

    /**
     * Helper: Bearer token → user_id (Sanctum personal_access_tokens).
     * Returns null if header missing/invalid or token not found.
     */
    private function tokenUserId(Request $request): ?int
    {
        $header = $request->header('Authorization');
        if (!$header || !preg_match('/Bearer\s(\S+)/', (string)$header, $m)) {
            return null;
        }

        $raw = $m[1];
        $hash = hash('sha256', $raw);

        $rec = DB::table('personal_access_tokens')
            ->where('token', $hash)
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();

        return $rec?->tokenable_id ?? null;
    }

}
