<?php

use Aws\S3\S3Client;
use Aws\Credentials\Credentials;

if (! function_exists('guessMimeFromExtension')) {
    function guessMimeFromExtension(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        // Umum
        return match ($ext) {
            'pdf'            => 'application/pdf',
            'png'            => 'image/png',
            'jpg', 'jpeg'    => 'image/jpeg',
            'webp'           => 'image/webp',
            'gif'            => 'image/gif',
            'svg'            => 'image/svg+xml',
            'txt'            => 'text/plain; charset=utf-8',
            'csv'            => 'text/csv; charset=utf-8',
            'json'           => 'application/json',
            'doc'            => 'application/msword',
            'docx'           => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'            => 'application/vnd.ms-excel',
            'xlsx'           => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'            => 'application/vnd.ms-powerpoint',
            'pptx'           => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip'            => 'application/zip',
            'rar'            => 'application/vnd.rar',
            '7z'             => 'application/x-7z-compressed',
            default          => 'application/octet-stream',
        };
    }
}

if (! function_exists('dispositionForMime')) {
    // Tentukan inline/attachment berdasarkan mime
    function dispositionForMime(string $mime): string
    {
        return str_starts_with($mime, 'image/')
            || $mime === 'application/pdf'
            || str_starts_with($mime, 'text/')
            ? 'inline'
            : 'attachment';
    }
}

if (! function_exists('signMinioUrlSmart')) {
    /**
     * Presigned URL dengan header respons yang sesuai jenis file.
     */
    function signMinioUrlSmart(
        string $bucket,
        string $key,
        int $minutes = 10,
        ?string $downloadName = null,
        ?string $forceMime = null,
    ): string {
        $client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
            // HOST publik yang diakses browser (bukan "minio:9000")
            'endpoint'=> rtrim(env('AWS_TEMPORARY_URL', 'http://localhost:9000'), '/'),
            'use_path_style_endpoint' => true,
            'signature_version'       => 'v4',
            'credentials' => new Credentials(
                env('AWS_ACCESS_KEY_ID'),
                env('AWS_SECRET_ACCESS_KEY')
            ),
        ]);

        $key = ltrim($key, '/');
        $mime = $forceMime ?: guessMimeFromExtension($key);
        $disp = dispositionForMime($mime);
        $filename = $downloadName ?: basename($key);

        $cmd = $client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key'    => $key,

            // Header respons untuk browser
            'ResponseContentType'        => $mime,
            'ResponseContentDisposition' => $disp . '; filename="' . addslashes($filename) . '"',
            'ResponseCacheControl'       => 'public, max-age=86400', // cache 1 hari
        ]);

        $req = $client->createPresignedRequest($cmd, now()->addMinutes($minutes));
        return (string) $req->getUri();
    }
}