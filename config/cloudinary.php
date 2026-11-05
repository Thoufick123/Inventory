<?php
/**
 * Cloudinary Configuration
 * Credentials loaded from .env — never hard-coded.
 */

return [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', ''),
    'api_key'    => env('CLOUDINARY_API_KEY', ''),
    'api_secret' => env('CLOUDINARY_API_SECRET', ''),
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', ''),
    'folder'     => 'grm_inventory',
];
