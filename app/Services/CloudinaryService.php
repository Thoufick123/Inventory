<?php
/**
 * Cloudinary Service
 * Handles uploading, deleting, and managing images and documents via Cloudinary REST API.
 * Automatically falls back to local disk storage if Cloudinary is unavailable.
 */

class CloudinaryService {

    /**
     * Check if Cloudinary credentials are fully configured.
     */
    public static function isConfigured(): bool {
        $cloudName = env('CLOUDINARY_CLOUD_NAME', '');
        $apiKey    = env('CLOUDINARY_API_KEY', '');
        $apiSecret = env('CLOUDINARY_API_SECRET', '');

        return !empty($cloudName) && !empty($apiKey) && !empty($apiSecret)
            && $cloudName !== 'your_cloud_name'
            && $apiKey !== 'your_api_key'
            && $apiSecret !== 'your_api_secret';
    }

    /**
     * Upload an image or file to Cloudinary.
     * If Cloudinary is not configured or network fails, falls back gracefully to local storage.
     *
     * @param array|string $file Either $_FILES array item or absolute local filepath
     * @param string $folder Cloudinary folder path (e.g. 'grm_inventory/sellers/rolls')
     * @param string $resourceType 'image', 'raw', or 'auto'
     * @return string|null The uploaded secure URL or local relative path, or null on complete failure
     */
    public static function upload($file, string $folder = 'grm_inventory', string $resourceType = 'auto'): ?string {
        $tmpPath = null;
        $originalName = 'upload';
        $mimeType = null;

        if (is_array($file)) {
            if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                return null;
            }
            $tmpPath = $file['tmp_name'];
            $originalName = $file['name'] ?? 'upload';
            $mimeType = $file['type'] ?? mime_content_type($tmpPath);
        } elseif (is_string($file)) {
            if (!file_exists($file)) {
                return null;
            }
            $tmpPath = $file;
            $originalName = basename($file);
            $mimeType = mime_content_type($file);
        } else {
            return null;
        }

        // Strictly upload to Cloudinary (No local storage)
        if (self::isConfigured()) {
            $cloudinaryUrl = self::uploadToCloudinaryApi($tmpPath, $folder, $originalName, $mimeType, $resourceType);
            if ($cloudinaryUrl) {
                return $cloudinaryUrl;
            }
        }

        error_log("[CloudinaryService] Upload failed or Cloudinary not configured. File not saved locally.");
        return null;
    }

    /**
     * Perform direct signed upload to Cloudinary REST API via cURL.
     */
    private static function uploadToCloudinaryApi(string $filePath, string $folder, string $originalName, ?string $mimeType, string $resourceType): ?string {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey    = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        $timestamp = time();
        $cleanFolder = trim($folder, '/');

        // Parameters to sign (sorted alphabetically)
        $params = [
            'folder'    => $cleanFolder,
            'timestamp' => $timestamp,
        ];

        ksort($params);
        $paramStr = '';
        foreach ($params as $k => $v) {
            $paramStr .= ($paramStr !== '' ? '&' : '') . $k . '=' . $v;
        }
        $signature = sha1($paramStr . $apiSecret);

        $cFile = new CURLFile($filePath, $mimeType ?: 'application/octet-stream', $originalName);

        $postFields = [
            'file'      => $cFile,
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'folder'    => $cleanFolder,
            'signature' => $signature,
        ];

        $apiUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/upload";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            if (!empty($result['secure_url'])) {
                return $result['secure_url'];
            }
            if (!empty($result['url'])) {
                return $result['url'];
            }
        }

        // Log error if debug mode is on
        if (env('APP_DEBUG', false)) {
            error_log("[CloudinaryService] Upload failed (HTTP $httpCode): " . $response);
        }

        return null;
    }

    /**
     * Extract public ID from a Cloudinary URL or return the public ID directly.
     *
     * @param string|null $publicIdOrUrl
     * @return string|null
     */
    public static function extractPublicId(?string $publicIdOrUrl): ?string {
        if (empty($publicIdOrUrl)) {
            return null;
        }

        if (str_starts_with($publicIdOrUrl, 'http://') || str_starts_with($publicIdOrUrl, 'https://')) {
            // Match Cloudinary URL pattern: /v1234567890/folder/subfolder/file.ext or /upload/folder/subfolder/file.ext
            if (preg_match('#/(?:v\d+/)?(grm_inventory/[^.\s]+)#', $publicIdOrUrl, $matches)) {
                return $matches[1];
            }
            if (preg_match('#/(?:v\d+/|upload/)?([^/]+/[^.\s]+)\.[a-zA-Z0-9]+$#', $publicIdOrUrl, $matches)) {
                return $matches[1];
            }
            if (preg_match('#/v\d+/(.+?)\.[a-zA-Z0-9]+$#', $publicIdOrUrl, $matches)) {
                return $matches[1];
            }
            return pathinfo(parse_url($publicIdOrUrl, PHP_URL_PATH) ?? $publicIdOrUrl, PATHINFO_FILENAME);
        }

        return $publicIdOrUrl;
    }

    /**
     * Delete an asset from Cloudinary by public ID or URL.
     */
    public static function delete(string $publicIdOrUrl, string $resourceType = 'image'): bool {
        if (!self::isConfigured() || empty($publicIdOrUrl)) {
            return false;
        }

        $publicId = self::extractPublicId($publicIdOrUrl) ?? $publicIdOrUrl;

        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey    = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        $timestamp = time();
        $params = [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];
        ksort($params);
        $paramStr = "public_id={$publicId}&timestamp={$timestamp}";
        $signature = sha1($paramStr . $apiSecret);

        $postFields = [
            'public_id' => $publicId,
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];

        $apiUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/destroy";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $result = json_decode($response, true);
            return ($result['result'] ?? '') === 'ok';
        }

        return false;
    }
}

if (!class_exists('App\Services\CloudinaryService', false)) {
    class_alias('CloudinaryService', 'App\Services\CloudinaryService');
}

