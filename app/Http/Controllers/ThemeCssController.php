<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThemeCssController extends Controller
{
    // FQCN used in personal_access_tokens.tokenable_type (same as your UserController)
    private const USER_TYPE = 'App\\Models\\User';

    /**
     * GET /assets/css/theme/user.css?token=PLAINTOKEN
     * Returns a minimal CSS that overrides :root vars for the authenticated user's active theme.
     * Safe to include after your main.css.
     */
    public function userCss(Request $request)
    {
        $t0 = microtime(true);
        Log::info('theme.userCss: start', [
            'ip'       => $request->ip(),
            'ua'       => substr((string) $request->header('User-Agent'), 0, 200),
            'path'     => $request->path(),
            'query'    => $request->query(),
        ]);

        try {
            // 1) Read token
            $plain = $request->query('token', null);
            $tokenSource = 'query';
            if (!$plain && $request->hasCookie('auth_token')) {
                $plain = $request->cookie('auth_token');
                $tokenSource = 'cookie';
            }

            Log::info('theme.userCss: token read', [
                'has_token'   => (bool) $plain,
                'token_source'=> $tokenSource,
                'token_masked'=> $this->mask($plain),
            ]);

            // If no token -> return empty CSS
            if (!$plain) {
                $css = "/* no token provided; no theme override */\n";
                $resp = $this->cssResponse($css);
                $this->logFinish($t0, 'no_token', strlen($css), 0);
                return $resp;
            }

            // 2) Resolve token -> user_id
            $hash = hash('sha256', $plain);
            $tokenRow = DB::table('personal_access_tokens')
                ->where('token', $hash)
                ->where('tokenable_type', self::USER_TYPE)
                ->first();

            Log::info('theme.userCss: token lookup', [
                'found'   => (bool) $tokenRow,
                'user_id' => $tokenRow->tokenable_id ?? null,
            ]);

            if (!$tokenRow) {
                $css = "/* invalid token; no theme override */\n";
                $resp = $this->cssResponse($css);
                $this->logFinish($t0, 'invalid_token', strlen($css), 0);
                return $resp;
            }

            $userId = (int) $tokenRow->tokenable_id;

            // 3) Find active mapping
            $map = DB::table('user_themes')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            Log::info('theme.userCss: mapping lookup', [
                'user_id'   => $userId,
                'has_map'   => (bool) $map,
                'theme_id'  => $map->theme_id ?? null,
                'map_id'    => $map->id ?? null,
            ]);

            if (!$map) {
                $css = "/* user has no active theme; no override */\n";
                $resp = $this->cssResponse($css);
                $this->logFinish($t0, 'no_active_mapping', strlen($css), 0);
                return $resp;
            }

            // 4) Load theme
            $theme = DB::table('themes')->where('id', $map->theme_id)->first();
            Log::info('theme.userCss: theme lookup', [
                'theme_id'  => $map->theme_id,
                'found'     => (bool) $theme,
                'theme_name'=> $theme->name ?? null,
            ]);

            if (!$theme) {
                $css = "/* theme not found; no override */\n";
                $resp = $this->cssResponse($css);
                $this->logFinish($t0, 'theme_missing', strlen($css), 0);
                return $resp;
            }

            // 5) Build CSS
            $vars = [
                'primary_color'   => '--primary-color',
                'secondary_color' => '--secondary-color',
                'accent_color'    => '--accent-color',
                'light_color'     => '--light-color',
                'border_color'    => '--border-color',
                'text_color'      => '--text-color',
                'bg_body'         => '--bg-body',
                'info_color'      => '--info-color',
                'success_color'   => '--success-color',
                'warning_color'   => '--warning-color',
                'danger_color'    => '--danger-color',
                'font_sans'       => '--font-sans',
                'font_head'       => '--font-head',
            ];

            $lines = [];
            $countSet = 0;
            foreach ($vars as $col => $cssVar) {
                if (isset($theme->$col) && trim((string)$theme->$col) !== '') {
                    $val = trim((string)$theme->$col);
                    if (in_array($col, ['font_sans','font_head'], true)) {
                        if (strpos($val, ' ') !== false && !preg_match('/(^[\'"].*[\'"]$)/', $val)) {
                            $val = "'{$val}'";
                        }
                    }
                    $lines[] = "  {$cssVar}: {$val};";
                    $countSet++;
                }
            }

            if (isset($theme->app_name) && trim((string)$theme->app_name) !== '') {
                $safeApp = addcslashes($theme->app_name, "\\'\"\n\r");
                $lines[] = "  --app-name: '{$safeApp}';";
                $countSet++;
            }

            $css = ":root{\n" . implode("\n", $lines) . "\n}\n";

            Log::info('theme.userCss: css built', [
                'vars_set'   => $countSet,
                'css_length' => strlen($css),
            ]);

            $cacheSeconds = 300;
            $resp = $this->cssResponse($css, $cacheSeconds);
            $this->logFinish($t0, 'ok', strlen($css), $cacheSeconds, [
                'user_id'  => $userId,
                'theme_id' => $map->theme_id,
            ]);
            return $resp;

        } catch (\Throwable $e) {
            Log::error('theme.userCss: exception', [
                'error'   => $e->getMessage(),
                'trace'   => substr($e->getTraceAsString(), 0, 2000),
            ]);
            $css = "/* internal error; no override */\n";
            $resp = $this->cssResponse($css);
            $this->logFinish($t0, 'error', strlen($css), 0);
            return $resp;
        }
    }

    /**
     * (Optional) Preview a theme by id without auth, useful for an admin preview screen.
     * GET /assets/css/theme/preview/{themeId}.css
     */
    public function preview(Request $request, int $themeId)
    {
        $t0 = microtime(true);
        Log::info('theme.preview: start', [
            'ip'      => $request->ip(),
            'ua'      => substr((string) $request->header('User-Agent'), 0, 200),
            'themeId' => $themeId,
        ]);

        try {
            $theme = DB::table('themes')->where('id', $themeId)->first();
            Log::info('theme.preview: theme lookup', [
                'found'      => (bool) $theme,
                'theme_name' => $theme->name ?? null,
            ]);
            if (!$theme) {
                $css = "/* preview: theme not found */\n";
                $resp = $this->cssResponse($css);
                $this->logFinish($t0, 'preview_theme_missing', strlen($css), 0);
                return $resp;
            }

            $vars = [
                'primary_color'   => '--primary-color',
                'secondary_color' => '--secondary-color',
                'accent_color'    => '--accent-color',
                'light_color'     => '--light-color',
                'border_color'    => '--border-color',
                'text_color'      => '--text-color',
                'bg_body'         => '--bg-body',
                'info_color'      => '--info-color',
                'success_color'   => '--success-color',
                'warning_color'   => '--warning-color',
                'danger_color'    => '--danger-color',
                'font_sans'       => '--font-sans',
                'font_head'       => '--font-head',
            ];
            $lines = [];
            $countSet = 0;
            foreach ($vars as $col => $cssVar) {
                if (isset($theme->$col) && trim((string)$theme->$col) !== '') {
                    $val = trim((string)$theme->$col);
                    if (in_array($col, ['font_sans','font_head'], true)) {
                        if (strpos($val, ' ') !== false && !preg_match('/(^[\'"].*[\'"]$)/', $val)) {
                            $val = "'{$val}'";
                        }
                    }
                    $lines[] = "  {$cssVar}: {$val};";
                    $countSet++;
                }
            }
            if (isset($theme->app_name) && trim((string)$theme->app_name) !== '') {
                $safeApp = addcslashes($theme->app_name, "\\'\"\n\r");
                $lines[] = "  --app-name: '{$safeApp}';";
                $countSet++;
            }

            $css = ":root{\n" . implode("\n", $lines) . "\n}\n";
            Log::info('theme.preview: css built', [
                'vars_set'   => $countSet,
                'css_length' => strlen($css),
            ]);

            $cacheSeconds = 60;
            $resp = $this->cssResponse($css, $cacheSeconds);
            $this->logFinish($t0, 'ok_preview', strlen($css), $cacheSeconds, [
                'theme_id' => $themeId,
            ]);
            return $resp;

        } catch (\Throwable $e) {
            Log::error('theme.preview: exception', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);
            $css = "/* preview: internal error */\n";
            $resp = $this->cssResponse($css);
            $this->logFinish($t0, 'error_preview', strlen($css), 0);
            return $resp;
        }
    }

    /** Small helper to return a text/css response with optional short caching. */
    private function cssResponse(string $css, int $cacheSeconds = 0)
    {
        $resp = response($css, Response::HTTP_OK, ['Content-Type' => 'text/css; charset=UTF-8']);
        if ($cacheSeconds > 0) {
            $resp->header('Cache-Control', "public, max-age={$cacheSeconds}");
        } else {
            $resp->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }
        return $resp;
    }

    /** Mask secrets (tokens) for logs: keep first 4 + last 4 chars. */
    private function mask(?string $val): ?string
    {
        if (!$val) return null;
        $len = strlen($val);
        if ($len <= 8) return str_repeat('*', $len);
        return substr($val, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($val, -4);
        // e.g. abcd************wxyz
    }

    /** Standardized "finish" log with timing and extra fields. */
    private function logFinish(float $t0, string $status, int $bytes, int $cacheSeconds, array $extra = []): void
    {
        $t1 = microtime(true);
        Log::info('theme.css: finish', array_merge([
            'status'        => $status,
            'duration_ms'   => round(($t1 - $t0) * 1000, 2),
            'resp_bytes'    => $bytes,
            'cache_seconds' => $cacheSeconds,
        ], $extra));
    }
}
