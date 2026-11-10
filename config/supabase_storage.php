<?php
declare(strict_types=1);

function digilibSupabaseStorageConfig(bool $requireServiceKey = false): array
{
    $localConfigPath = __DIR__ . '/supabase.local.php';
    $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
    if (!is_array($localConfig)) {
        throw new RuntimeException('Supabase local configuration must return an array.');
    }

    $setting = static function (string $environmentKey, string $configKey, string $default = '') use ($localConfig): string {
        $environmentValue = getenv($environmentKey);
        if ($environmentValue !== false && $environmentValue !== '') {
            return $environmentValue;
        }

        $configValue = $localConfig[$configKey] ?? null;
        return is_string($configValue) && $configValue !== '' ? $configValue : $default;
    };

    $url = rtrim($setting('SUPABASE_URL', 'url', 'https://dumbtekptaldxrteeaeg.supabase.co'), '/');
    $bucket = $setting('SUPABASE_STORAGE_BUCKET', 'bucket', 'book-covers');
    $serviceKey = $setting('SUPABASE_SERVICE_ROLE_KEY', 'service_role_key');
    $urlParts = parse_url($url);

    if (!is_array($urlParts) || ($urlParts['scheme'] ?? '') !== 'https' || empty($urlParts['host'])) {
        throw new RuntimeException('Supabase URL must be a valid HTTPS URL.');
    }
    if ($bucket === '' || strpos($bucket, '/') !== false) {
        throw new RuntimeException('Supabase Storage bucket name is invalid.');
    }
    if ($requireServiceKey && $serviceKey === '') {
        throw new RuntimeException('SUPABASE_SERVICE_ROLE_KEY is not configured.');
    }

    return [
        'url' => $url,
        'bucket' => $bucket,
        'service_role_key' => $serviceKey,
    ];
}

function digilibIsSupabaseBookCover(?string $coverPath): bool
{
    return is_string($coverPath)
        && preg_match('~\Abooks/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $coverPath) === 1;
}

function digilibBookCoverUrl(?string $coverPath): ?string
{
    if ($coverPath === null || $coverPath === '') {
        return null;
    }

    if (digilibIsSupabaseBookCover($coverPath)) {
        $config = digilibSupabaseStorageConfig();
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $coverPath)));
        return $config['url'] . '/storage/v1/object/public/' . rawurlencode($config['bucket']) . '/' . $encodedPath;
    }

    if (basename($coverPath) !== $coverPath) {
        return null;
    }

    $coverDirectory = realpath(__DIR__ . '/../assets/uploads/covers');
    $coverFile = realpath(__DIR__ . '/../assets/uploads/covers/' . $coverPath);
    if ($coverDirectory === false
        || $coverFile === false
        || strpos($coverFile, $coverDirectory . DIRECTORY_SEPARATOR) !== 0
        || !is_file($coverFile)) {
        return null;
    }

    return digilibUrl('assets/uploads/covers/' . rawurlencode($coverPath));
}

function digilibUploadBookCover(string $temporaryPath, string $mimeType, string $extension): string
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL extension is required for Supabase Storage uploads.');
    }

    $config = digilibSupabaseStorageConfig(true);
    $objectPath = 'books/' . bin2hex(random_bytes(16)) . '.' . $extension;
    $fileContents = file_get_contents($temporaryPath);
    if ($fileContents === false) {
        throw new RuntimeException('Uploaded cover file could not be read.');
    }

    $url = $config['url'] . '/storage/v1/object/' . rawurlencode($config['bucket']) . '/'
        . implode('/', array_map('rawurlencode', explode('/', $objectPath)));
    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Supabase Storage request could not be initialized.');
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fileContents,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $config['service_role_key'],
            'apikey: ' . $config['service_role_key'],
            'Content-Type: ' . $mimeType,
            'Cache-Control: max-age=3600',
            'x-upsert: false',
        ],
    ]);
    $response = curl_exec($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false || $httpStatus < 200 || $httpStatus >= 300) {
        error_log('DIGILIB Supabase cover upload failed. HTTP ' . $httpStatus . '; ' . $curlError . '; ' . (string) $response);
        throw new RuntimeException('Supabase Storage rejected the cover upload (HTTP ' . $httpStatus . ').');
    }

    return $objectPath;
}

function digilibRemoveBookCover(?string $coverPath): void
{
    if (digilibIsSupabaseBookCover($coverPath)) {
        try {
            if (!function_exists('curl_init')) {
                throw new RuntimeException('PHP cURL extension is required for Supabase Storage.');
            }

            $config = digilibSupabaseStorageConfig(true);
            $url = $config['url'] . '/storage/v1/object/' . rawurlencode($config['bucket']);
            $body = json_encode(['prefixes' => [$coverPath]], JSON_THROW_ON_ERROR);
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('Supabase Storage request could not be initialized.');
            }

            curl_setopt_array($curl, [
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config['service_role_key'],
                    'apikey: ' . $config['service_role_key'],
                    'Content-Type: application/json',
                ],
            ]);
            $response = curl_exec($curl);
            $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($response === false || $httpStatus < 200 || $httpStatus >= 300) {
                throw new RuntimeException('Supabase Storage rejected the cover deletion (HTTP ' . $httpStatus . '): ' . $curlError . ' ' . (string) $response);
            }
        } catch (Throwable $exception) {
            error_log('DIGILIB could not remove Supabase book cover: ' . $exception->getMessage());
        }
        return;
    }

    if ($coverPath === null || $coverPath === '' || basename($coverPath) !== $coverPath) {
        return;
    }

    $coverDirectory = realpath(__DIR__ . '/../assets/uploads/covers');
    $coverFile = realpath(__DIR__ . '/../assets/uploads/covers/' . $coverPath);
    if ($coverDirectory !== false
        && $coverFile !== false
        && strpos($coverFile, $coverDirectory . DIRECTORY_SEPARATOR) === 0
        && is_file($coverFile)
        && !unlink($coverFile)) {
        error_log('DIGILIB could not remove book cover: ' . $coverFile);
    }
}
