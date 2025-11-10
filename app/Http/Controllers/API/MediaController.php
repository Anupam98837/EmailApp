<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;

class MediaController extends Controller
{
    /**
     * Attach a correlation ID to each request for tracing.
     */
    private function correlationId(Request $request): string
    {
        if (! $request->attributes->has('corr_id')) {
            $request->attributes->set('corr_id', (string) Str::uuid());
        }
        return $request->attributes->get('corr_id');
    }

    /**
     * Extract authenticated user ID from Bearer token.
     * Logs every decision step.
     */
    private function getAuthenticatedUserId(Request $request): int
    {
        $corr = $this->correlationId($request);
        // Log::debug('Auth: start extracting user id', ['corr'=>$corr]);

        $header = $request->header('Authorization');
        if (! $header) {
            // Log::warning('Auth: missing Authorization header', ['corr'=>$corr]);
            abort(response()->json([
                'status'=>'error','message'=>'Token not provided'
            ], 401));
        }

        if (!preg_match('/Bearer\s(\S+)/', $header, $m)) {
            // Log::warning('Auth: malformed Authorization header', ['corr'=>$corr, 'header'=>$header]);
            abort(response()->json([
                'status'=>'error','message'=>'Invalid token format'
            ], 401));
        }

        $rawToken = $m[1];
        $tokenHash = hash('sha256', $rawToken);
        // Log::debug('Auth: computed token hash', ['corr'=>$corr, 'token_hash'=>$tokenHash]);

        $record = DB::table('personal_access_tokens')
            ->where('token', $tokenHash)
            ->where('tokenable_type', 'App\\Models\\User')
            ->first();

        if (! $record) {
            // Log::warning('Auth: token not found', ['corr'=>$corr, 'token_hash'=>$tokenHash]);
            abort(response()->json([
                'status'=>'error','message'=>'Invalid token'
            ], 401));
        }

        // Log::info('Auth: success', ['corr'=>$corr, 'user_id'=>$record->tokenable_id]);
        return (int) $record->tokenable_id;
    }

    /**
     * GET /api/media
     * List all media items for the authenticated user.
     */
    public function index(Request $request)
    {
        $start = microtime(true);
        $corr  = $this->correlationId($request);
        // Log::info('Media.index: request received', ['corr'=>$corr, 'query'=>$request->query()]);

        $userId = $this->getAuthenticatedUserId($request);

        // Log::debug('Media.index: querying DB', ['corr'=>$corr, 'user_id'=>$userId]);
        $items = DB::table('media')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Log::info('Media.index: success', [
        //     'corr'=>$corr,
        //     'user_id'=>$userId,
        //     'count'=>$items->count(),
        //     'duration_ms'=>round((microtime(true)-$start)*1000,2)
        // ]);

        return response()->json([
            'status'=>'success',
            'message'=>'Media items retrieved.',
            'data'=>$items,
        ], 200);
    }

    /**
     * POST /api/media
     * Upload a new media file.
     */
    public function store(Request $request)
    {
        $requestStart = microtime(true);
        $corr = $this->correlationId($request);
        // Log::info('Media.store: request received', [
        //     'corr'=>$corr,
        //     'content_type'=>$request->header('Content-Type'),
        //     'all_input_keys'=>array_keys($request->all())
        // ]);

        $userId = $this->getAuthenticatedUserId($request);

        // Log::debug('Media.store: starting validation', ['corr'=>$corr]);
        $v = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // <=10MB
        ]);

        if ($v->fails()) {
            // Log::warning('Media.store: validation failed', [
            //     'corr'=>$corr,
            //     'errors'=>$v->errors()->all()
            // ]);
            return response()->json([
                'status'=>'error',
                'message'=>'Validation failed.',
                'errors'=>$v->errors(),
            ], 422);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');

        // Additional safe checks: MIME & extension
        $originalName = $file->getClientOriginalName();
        $mime         = $file->getClientMimeType();
        $ext          = strtolower($file->getClientOriginalExtension());

        // Log::debug('Media.store: file meta', [
        //     'corr'=>$corr,
        //     'original_name'=>$originalName,
        //     'mime'=>$mime,
        //     'ext'=>$ext,
        //     'size_bytes'=>$file->getSize()
        // ]);

        // (Optional) restrict certain dangerous extensions
        $blocked = ['php','phtml','phar','cgi','exe','sh','bat','cmd','com','js'];
        if (in_array($ext, $blocked, true)) {
            // Log::warning('Media.store: blocked extension', ['corr'=>$corr,'ext'=>$ext]);
            return response()->json([
                'status'=>'error',
                'message'=>'File type not allowed.'
            ], 415);
        }

        $uuidName = (string) Str::uuid();
        $safeName = $uuidName . ($ext ? '.'.$ext : '');
        $destDir  = public_path("assets/media/{$userId}");

        // Log::debug('Media.store: ensuring destination directory', [
        //     'corr'=>$corr,
        //     'dest_dir'=>$destDir
        // ]);

        if (! File::exists($destDir)) {
            try {
                File::makeDirectory($destDir, 0755, true);
                // Log::info('Media.store: directory created', ['corr'=>$corr,'dest_dir'=>$destDir]);
            } catch (\Exception $e) {
                // Log::error('Media.store: failed to create directory', [
                //     'corr'=>$corr,
                //     'error'=>$e->getMessage()
                // ]);
                return response()->json([
                    'status'=>'error',
                    'message'=>'Server storage error (dir).'
                ], 500);
            }
        }

        $fullPath = $destDir . DIRECTORY_SEPARATOR . $safeName;
        // Log::debug('Media.store: moving uploaded file', [
        //     'corr'=>$corr,
        //     'temp_path'=>$file->getRealPath(),
        //     'target_path'=>$fullPath
        // ]);

        try {
            $file->move($destDir, $safeName);
        } catch (\Exception $e) {
            // Log::error('Media.store: move failed', ['corr'=>$corr,'error'=>$e->getMessage()]);
            return response()->json([
                'status'=>'error',
                'message'=>'Unable to store the file.'
            ], 500);
        }

        if (! File::exists($fullPath)) {
            // Log::error('Media.store: file missing after move', ['corr'=>$corr,'expected'=>$fullPath]);
            return response()->json([
                'status'=>'error',
                'message'=>'File persistence failure.'
            ], 500);
        }

        $relPath = "assets/media/{$userId}/{$safeName}";
        $publicUrl = asset($relPath);
        $sizeBytes = File::size($fullPath);

        // Log::debug('Media.store: preparing DB insert', [
        //     'corr'=>$corr,
        //     'rel_path'=>$relPath,
        //     'public_url'=>$publicUrl,
        //     'size_bytes'=>$sizeBytes
        // ]);

        try {
            $insertStart = microtime(true);
            $id = DB::table('media')->insertGetId([
                'user_id'    => $userId,
                'url'        => $publicUrl,
                'size'       => $sizeBytes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // Log::info('Media.store: DB insert success', [
            //     'corr'=>$corr,
            //     'media_id'=>$id,
            //     'insert_ms'=>round((microtime(true)-$insertStart)*1000,2)
            // ]);
        } catch (\Exception $e) {
            // Attempt cleanup if DB fails
            // Log::error('Media.store: DB insert failed, cleaning up file', [
            //     'corr'=>$corr,'error'=>$e->getMessage()
            // ]);
            try {
                if (File::exists($fullPath)) {
                    File::delete($fullPath);
                    Log::info('Media.store: orphan file removed after DB failure', ['corr'=>$corr]);
                }
            } catch (\Exception $cleanupEx) {
                // Log::error('Media.store: cleanup failed', [
                //     'corr'=>$corr,'cleanup_error'=>$cleanupEx->getMessage()
                // ]);
            }
            return response()->json([
                'status'=>'error',
                'message'=>'Failed to record file metadata.'
            ], 500);
        }

        // Log::info('Media.store: success', [
        //     'corr'=>$corr,
        //     'user_id'=>$userId,
        //     'media_url'=>$publicUrl,
        //     'duration_ms'=>round((microtime(true)-$requestStart)*1000,2)
        // ]);

        return response()->json([
            'status'=>'success',
            'message'=>'File uploaded.',
            'data'=>[
                'id'=>$id,
                'url'=>$publicUrl,
                'size'=>$sizeBytes,
            ],
        ], 201);
    }

    /**
     * DELETE /api/media/{id}
     * Delete a media file (DB + physical file).
     */
    public function destroy(Request $request, $id)
    {
        $start = microtime(true);
        $corr  = $this->correlationId($request);
        // Log::info('Media.destroy: request received', [
        //     'corr'=>$corr,
        //     'media_id'=>$id
        // ]);

        $userId = $this->getAuthenticatedUserId($request);

        // Log::debug('Media.destroy: locating media record', [
        //     'corr'=>$corr,'media_id'=>$id,'user_id'=>$userId
        // ]);

        $item = DB::table('media')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $item) {
            // Log::warning('Media.destroy: media not found', [
            //     'corr'=>$corr,'media_id'=>$id,'user_id'=>$userId
            // ]);
            return response()->json([
                'status'=>'error',
                'message'=>'Media not found.'
            ], 404);
        }

        // Derive local path from URL
        $parsedPath = parse_url($item->url, PHP_URL_PATH);
        $fullDiskPath = public_path(ltrim($parsedPath, '/'));

        // Log::debug('Media.destroy: computed disk path', [
        //     'corr'=>$corr,
        //     'disk_path'=>$fullDiskPath
        // ]);

        $fileDeleted = false;
        if (File::exists($fullDiskPath)) {
            try {
                File::delete($fullDiskPath);
                $fileDeleted = true;
                // Log::info('Media.destroy: file deleted from disk', [
                //     'corr'=>$corr,'disk_path'=>$fullDiskPath
                // ]);
            } catch (\Exception $e) {
                // Log::error('Media.destroy: disk delete failed (continuing to remove DB record)', [
                //     'corr'=>$corr,'error'=>$e->getMessage()
                // ]);
            }
        } else {
            // Log::warning('Media.destroy: file not present on disk', [
            //     'corr'=>$corr,'disk_path'=>$fullDiskPath
            // ]);
        }

        try {
            DB::table('media')->where('id', $id)->delete();
            // Log::info('Media.destroy: DB record deleted', [
            //     'corr'=>$corr,'media_id'=>$id
            // ]);
        } catch (\Exception $e) {
            // Log::error('Media.destroy: DB delete failed', [
            //     'corr'=>$corr,'media_id'=>$id,'error'=>$e->getMessage()
            // ]);
            return response()->json([
                'status'=>'error',
                'message'=>'Failed to delete media record.'
            ], 500);
        }

        // Log::info('Media.destroy: success', [
        //     'corr'=>$corr,
        //     'media_id'=>$id,
        //     'file_deleted'=>$fileDeleted,
        //     'duration_ms'=>round((microtime(true)-$start)*1000,2)
        // ]);

        return response()->json([
            'status'=>'success',
            'message'=>'Media deleted.'
        ], 200);
    }
}
