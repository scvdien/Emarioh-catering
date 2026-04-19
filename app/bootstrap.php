<?php
declare(strict_types=1);

const EMARIOH_BASE_PATH = __DIR__ . '/..';
const EMARIOH_DEFAULT_STORAGE_PATH = EMARIOH_BASE_PATH . '/storage';
const EMARIOH_DEFAULT_DB_HOST = '127.0.0.1';
const EMARIOH_DEFAULT_DB_PORT = 3306;
const EMARIOH_DEFAULT_DB_NAME = '';
const EMARIOH_DEFAULT_DB_USER = '';
const EMARIOH_DEFAULT_DB_PASS = '';
const EMARIOH_REMEMBER_COOKIE = 'emarioh_remember';
const EMARIOH_OTP_TTL = 300;
const EMARIOH_OTP_VERIFIED_TTL = 600;
const EMARIOH_REMEMBER_TTL = 2592000;
const EMARIOH_DEVELOPMENT_MODE = false;

date_default_timezone_set('Asia/Manila');

emarioh_enforce_https_request();
emarioh_send_dynamic_no_cache_headers();

if (!defined('EMARIOH_SKIP_SESSION_BOOTSTRAP') || EMARIOH_SKIP_SESSION_BOOTSTRAP !== true) {
    emarioh_prepare_storage_paths();
    emarioh_start_session();
}

function emarioh_send_dynamic_no_cache_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    $scriptName = strtolower(trim((string) ($_SERVER['SCRIPT_NAME'] ?? '')));

    if ($scriptName !== '' && str_ends_with($scriptName, '/media.php')) {
        return;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function emarioh_config(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $config = emarioh_runtime_config();
    return $config;
}

function emarioh_local_config_path(): string
{
    return EMARIOH_BASE_PATH . '/app/config.local.php';
}

function emarioh_runtime_config(): array
{
    static $runtimeConfig = null;

    if (is_array($runtimeConfig)) {
        return $runtimeConfig;
    }

    $config = [
        'development_mode' => EMARIOH_DEVELOPMENT_MODE,
        'app' => [
            'url' => '',
            'force_https' => false,
        ],
        'storage' => [
            'path' => EMARIOH_DEFAULT_STORAGE_PATH,
            'session_path' => EMARIOH_DEFAULT_STORAGE_PATH . '/sessions',
        ],
        'database' => [
            'host' => EMARIOH_DEFAULT_DB_HOST,
            'port' => EMARIOH_DEFAULT_DB_PORT,
            'name' => EMARIOH_DEFAULT_DB_NAME,
            'user' => EMARIOH_DEFAULT_DB_USER,
            'password' => EMARIOH_DEFAULT_DB_PASS,
            'auto_create' => false,
        ],
        'sms' => [
            'enabled' => false,
            'booking_status_enabled' => false,
            'provider' => 'semaphore',
            'semaphore' => [
                'api_key' => '',
                'endpoint' => 'https://api.semaphore.co/api/v4/otp',
                'messages_endpoint' => 'https://api.semaphore.co/api/v4/messages',
                'sender_name' => '',
                'timeout_seconds' => 15,
            ],
        ],
        'paymongo' => [
            'enabled' => false,
            'secret_key' => '',
            'public_key' => '',
            'webhook_secret' => '',
            'api_base' => 'https://api.paymongo.com/v1',
            'timeout_seconds' => 20,
        ],
    ];

    $localConfig = emarioh_load_local_config();

    if ($localConfig !== []) {
        $config = array_replace_recursive($config, $localConfig);
    }

    $config['development_mode'] = emarioh_env_bool(
        'EMARIOH_DEVELOPMENT_MODE',
        (bool) ($config['development_mode'] ?? EMARIOH_DEVELOPMENT_MODE)
    );
    $config['app']['url'] = rtrim(emarioh_env_string(
        'EMARIOH_APP_URL',
        trim((string) ($config['app']['url'] ?? ''))
    ), '/');
    $config['app']['force_https'] = emarioh_env_bool(
        'EMARIOH_FORCE_HTTPS',
        (bool) ($config['app']['force_https'] ?? false)
    );

    $storagePath = emarioh_resolve_path(
        emarioh_env_string('EMARIOH_STORAGE_PATH', (string) ($config['storage']['path'] ?? '')),
        EMARIOH_DEFAULT_STORAGE_PATH
    );
    $sessionPath = emarioh_resolve_path(
        emarioh_env_string('EMARIOH_SESSION_PATH', (string) ($config['storage']['session_path'] ?? '')),
        $storagePath . DIRECTORY_SEPARATOR . 'sessions'
    );
    $config['storage']['path'] = $storagePath;
    $config['storage']['session_path'] = $sessionPath;

    $config['database']['host'] = emarioh_env_string(
        'EMARIOH_DB_HOST',
        trim((string) ($config['database']['host'] ?? EMARIOH_DEFAULT_DB_HOST))
    );
    $config['database']['port'] = max(1, (int) emarioh_env_string(
        'EMARIOH_DB_PORT',
        (string) ($config['database']['port'] ?? EMARIOH_DEFAULT_DB_PORT)
    ));
    $config['database']['name'] = trim((string) emarioh_env_string(
        'EMARIOH_DB_NAME',
        trim((string) ($config['database']['name'] ?? EMARIOH_DEFAULT_DB_NAME))
    ));
    $config['database']['user'] = trim((string) emarioh_env_string(
        'EMARIOH_DB_USER',
        trim((string) ($config['database']['user'] ?? EMARIOH_DEFAULT_DB_USER))
    ));
    $config['database']['password'] = emarioh_env_string(
        'EMARIOH_DB_PASS',
        (string) ($config['database']['password'] ?? EMARIOH_DEFAULT_DB_PASS)
    );
    $config['database']['auto_create'] = emarioh_env_bool(
        'EMARIOH_DB_AUTO_CREATE',
        (bool) ($config['database']['auto_create'] ?? false)
    );
    $databaseManageSchemaDefault = array_key_exists('manage_schema', $config['database'])
        ? (bool) ($config['database']['manage_schema'] ?? false)
        : (emarioh_runtime_looks_local() || (bool) ($config['development_mode'] ?? EMARIOH_DEVELOPMENT_MODE));
    $config['database']['manage_schema'] = emarioh_env_bool(
        'EMARIOH_DB_MANAGE_SCHEMA',
        $databaseManageSchemaDefault
    );

    emarioh_validate_runtime_config($config);

    $runtimeConfig = $config;
    return $runtimeConfig;
}

function emarioh_load_local_config(): array
{
    static $localConfig = null;

    if (is_array($localConfig)) {
        return $localConfig;
    }

    $localConfigPath = emarioh_local_config_path();

    if (!is_file($localConfigPath)) {
        $localConfig = [];
        return $localConfig;
    }

    $loadedConfig = require $localConfigPath;
    $localConfig = is_array($loadedConfig) ? $loadedConfig : [];

    return $localConfig;
}

function emarioh_env_string(string $name, string $default = ''): string
{
    $value = getenv($name);

    if ($value !== false) {
        return (string) $value;
    }

    if (array_key_exists($name, $_ENV)) {
        return (string) $_ENV[$name];
    }

    if (array_key_exists($name, $_SERVER)) {
        return (string) $_SERVER[$name];
    }

    return $default;
}

function emarioh_value_looks_like_placeholder(string $value): bool
{
    $normalizedValue = strtolower(trim($value));

    if ($normalizedValue === '') {
        return false;
    }

    return str_contains($normalizedValue, 'replace-with')
        || str_contains($normalizedValue, 'paste-your')
        || str_contains($normalizedValue, 'example.com')
        || str_contains($normalizedValue, 'your-domain')
        || str_contains($normalizedValue, 'your-database-password');
}

function emarioh_ip_is_local_or_private(string $ipAddress): bool
{
    $normalizedIp = trim($ipAddress);

    if ($normalizedIp === '') {
        return false;
    }

    if (in_array($normalizedIp, ['127.0.0.1', '::1'], true)) {
        return true;
    }

    return preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $normalizedIp) === 1;
}

function emarioh_runtime_looks_local(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }

    $normalizedBasePath = strtolower(str_replace('\\', '/', EMARIOH_BASE_PATH));

    if (str_contains($normalizedBasePath, '/xampp/htdocs/')
        || str_contains($normalizedBasePath, '/mamp/htdocs/')
    ) {
        return true;
    }

    $hostCandidates = [
        trim((string) ($_SERVER['HTTP_HOST'] ?? '')),
        trim((string) ($_SERVER['SERVER_NAME'] ?? '')),
    ];

    foreach ($hostCandidates as $hostCandidate) {
        $normalizedHost = strtolower(trim((string) preg_replace('/:\d+$/', '', $hostCandidate)));

        if ($normalizedHost === '') {
            continue;
        }

        if (in_array($normalizedHost, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (preg_match('/(?:^|\.)((test|local|localhost))$/', $normalizedHost) === 1) {
            return true;
        }
    }

    $ipCandidates = [
        trim((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
    ];

    foreach ($ipCandidates as $ipCandidate) {
        if (emarioh_ip_is_local_or_private($ipCandidate)) {
            return true;
        }
    }

    return false;
}

function emarioh_validate_runtime_config(array $config): void
{
    if ((bool) ($config['development_mode'] ?? EMARIOH_DEVELOPMENT_MODE)) {
        return;
    }

    $databaseConfig = is_array($config['database'] ?? null) ? $config['database'] : [];
    $databaseHost = strtolower(trim((string) ($databaseConfig['host'] ?? '')));
    $databaseName = trim((string) ($databaseConfig['name'] ?? ''));
    $databaseUser = trim((string) ($databaseConfig['user'] ?? ''));
    $databasePassword = (string) ($databaseConfig['password'] ?? '');
    $appConfig = is_array($config['app'] ?? null) ? $config['app'] : [];
    $configuredAppUrl = trim((string) ($appConfig['url'] ?? ''));
    $appForceHttps = (bool) ($appConfig['force_https'] ?? false);
    $configuredAppScheme = $configuredAppUrl !== ''
        ? strtolower(trim((string) parse_url($configuredAppUrl, PHP_URL_SCHEME)))
        : '';
    $configuredAppHost = $configuredAppUrl !== ''
        ? trim((string) parse_url($configuredAppUrl, PHP_URL_HOST))
        : '';
    $isLocalRuntime = emarioh_runtime_looks_local();
    $missingKeys = [];
    $placeholderKeys = [];
    $invalidKeys = [];

    if ($databaseName === '') {
        $missingKeys[] = 'database.name';
    }

    if ($databaseUser === '') {
        $missingKeys[] = 'database.user';
    }

    if (emarioh_value_looks_like_placeholder($databaseHost)) {
        $placeholderKeys[] = 'database.host';
    }

    if (emarioh_value_looks_like_placeholder($databaseName)) {
        $placeholderKeys[] = 'database.name';
    }

    if (emarioh_value_looks_like_placeholder($databaseUser)) {
        $placeholderKeys[] = 'database.user';
    }

    if (emarioh_value_looks_like_placeholder($databasePassword)) {
        $placeholderKeys[] = 'database.password';
    }

    if ($configuredAppUrl !== '' && emarioh_value_looks_like_placeholder($configuredAppUrl)) {
        $placeholderKeys[] = 'app.url';
    }

    if ($configuredAppUrl !== '' && (
        !emarioh_is_absolute_url($configuredAppUrl)
        || !in_array($configuredAppScheme, ['http', 'https'], true)
        || $configuredAppHost === ''
    )) {
        $invalidKeys[] = 'app.url';
    }

    $usesLocalXamppDatabaseDefaults = in_array($databaseHost, ['127.0.0.1', 'localhost'], true)
        && $databaseName === 'emarioh_catering_db'
        && $databaseUser === 'root'
        && $databasePassword === '';

    if (!$isLocalRuntime) {
        if ($configuredAppUrl === '') {
            $missingKeys[] = 'app.url';
        } elseif ($configuredAppScheme !== 'https') {
            $invalidKeys[] = 'app.url (must use https)';
        }

        if (!$appForceHttps) {
            $invalidKeys[] = 'app.force_https';
        }
    } elseif ($appForceHttps && $configuredAppUrl !== '' && $configuredAppScheme !== 'https') {
        $invalidKeys[] = 'app.url (must use https when app.force_https is true)';
    }

    if ($missingKeys === [] && $placeholderKeys === [] && $invalidKeys === [] && (!$usesLocalXamppDatabaseDefaults || $isLocalRuntime)) {
        return;
    }

    $message = 'Runtime configuration is incomplete for live hosting. ';

    if (is_file(emarioh_local_config_path())) {
        $message .= 'Update app/config.local.php or the EMARIOH_APP_URL, EMARIOH_FORCE_HTTPS, and EMARIOH_DB_* environment variables before serving this site outside localhost.';
    } else {
        $message .= 'Create app/config.local.php from app/config.local.php.example or set the EMARIOH_APP_URL, EMARIOH_FORCE_HTTPS, and EMARIOH_DB_* environment variables before serving this site outside localhost.';
    }

    if ($missingKeys !== []) {
        $message .= ' Missing: ' . implode(', ', $missingKeys) . '.';
    }

    if ($placeholderKeys !== []) {
        $message .= ' Replace placeholder values in: ' . implode(', ', array_unique($placeholderKeys)) . '.';
    }

    if ($invalidKeys !== []) {
        $message .= ' Invalid or missing live app settings: ' . implode(', ', array_unique($invalidKeys)) . '.';
    }

    if ($usesLocalXamppDatabaseDefaults && !$isLocalRuntime) {
        $message .= ' Local XAMPP database defaults are still active.';
    }

    throw new RuntimeException($message);
}

function emarioh_env_bool(string $name, bool $default = false): bool
{
    $rawValue = emarioh_env_string($name, '');

    if ($rawValue === '') {
        return $default;
    }

    $parsedValue = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $parsedValue === null ? $default : $parsedValue;
}

function emarioh_is_absolute_url(string $value): bool
{
    return preg_match('#^[A-Za-z][A-Za-z0-9+.-]*://#', trim($value)) === 1;
}

function emarioh_resolve_path(string $path, string $defaultPath): string
{
    $normalizedPath = trim($path);

    if ($normalizedPath === '') {
        $normalizedPath = $defaultPath;
    }

    $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalizedPath);

    if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $normalizedPath) === 1
        || str_starts_with($normalizedPath, '/')
        || str_starts_with($normalizedPath, '\\\\')
    ) {
        $resolvedAbsolutePath = realpath($normalizedPath);
        return $resolvedAbsolutePath !== false ? $resolvedAbsolutePath : $normalizedPath;
    }

    $resolvedRelativePath = EMARIOH_BASE_PATH . DIRECTORY_SEPARATOR . ltrim($normalizedPath, "\\/");
    $resolvedAbsolutePath = realpath($resolvedRelativePath);

    return $resolvedAbsolutePath !== false ? $resolvedAbsolutePath : $resolvedRelativePath;
}

function emarioh_storage_path(): string
{
    return (string) (emarioh_config()['storage']['path'] ?? EMARIOH_DEFAULT_STORAGE_PATH);
}

function emarioh_ensure_directory_exists(string $path, string $errorMessage): void
{
    if (!is_dir($path)
        && !mkdir($path, 0775, true)
        && !is_dir($path)
    ) {
        throw new RuntimeException($errorMessage);
    }
}

function emarioh_session_path(): string
{
    return (string) (emarioh_config()['storage']['session_path'] ?? (emarioh_storage_path() . DIRECTORY_SEPARATOR . 'sessions'));
}

function emarioh_prepare_storage_paths(): void
{
    $sessionPath = emarioh_session_path();
    emarioh_ensure_directory_exists(
        $sessionPath,
        'Session storage directory could not be created. Check session_path and write permissions.'
    );
}

function emarioh_is_development_mode(): bool
{
    return (bool) (emarioh_config()['development_mode'] ?? EMARIOH_DEVELOPMENT_MODE);
}

function emarioh_database_config(): array
{
    $databaseConfig = emarioh_config()['database'] ?? [];

    return [
        'host' => trim((string) ($databaseConfig['host'] ?? EMARIOH_DEFAULT_DB_HOST)),
        'port' => max(1, (int) ($databaseConfig['port'] ?? EMARIOH_DEFAULT_DB_PORT)),
        'name' => trim((string) ($databaseConfig['name'] ?? EMARIOH_DEFAULT_DB_NAME)),
        'user' => trim((string) ($databaseConfig['user'] ?? EMARIOH_DEFAULT_DB_USER)),
        'password' => (string) ($databaseConfig['password'] ?? EMARIOH_DEFAULT_DB_PASS),
        'auto_create' => (bool) ($databaseConfig['auto_create'] ?? false),
        'manage_schema' => (bool) ($databaseConfig['manage_schema'] ?? (emarioh_runtime_looks_local() || emarioh_is_development_mode())),
    ];
}

function emarioh_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = emarioh_request_is_https();

    session_name('emarioh_session');
    session_save_path(emarioh_session_path());
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function emarioh_db_server(): PDO
{
    static $serverDb = null;

    if ($serverDb instanceof PDO) {
        return $serverDb;
    }

    $serverDb = emarioh_create_database_connection(false);

    return $serverDb;
}

function emarioh_db(): PDO
{
    static $db = null;

    if ($db instanceof PDO) {
        return $db;
    }

    $databaseConfig = emarioh_database_config();

    if ($databaseConfig['auto_create']) {
        emarioh_ensure_database_exists(emarioh_db_server(), (string) $databaseConfig['name']);
    }

    $db = emarioh_create_database_connection(true);

    if (!empty($databaseConfig['manage_schema'])) {
        emarioh_ensure_schema($db);
    } else {
        emarioh_assert_schema_is_ready($db);
    }

    emarioh_cleanup_expired_records($db);

    return $db;
}

function emarioh_create_database_connection(bool $withDatabase = true): PDO
{
    $databaseConfig = emarioh_database_config();
    $databaseName = trim((string) ($databaseConfig['name'] ?? ''));

    if ($withDatabase && $databaseName === '') {
        throw new RuntimeException('Database name is missing from the configuration.');
    }

    $dsn = $withDatabase
        ? sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $databaseConfig['host'],
            $databaseConfig['port'],
            $databaseName
        )
        : sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            $databaseConfig['host'],
            $databaseConfig['port']
        );

    try {
        return new PDO(
            $dsn,
            (string) ($databaseConfig['user'] ?? ''),
            (string) ($databaseConfig['password'] ?? ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $exception) {
        $message = $withDatabase
            ? 'Database connection failed. Check the configured database name, username, and password.'
            : 'Database server connection failed. Check the configured host, port, username, and password.';

        if (emarioh_is_development_mode()) {
            $message .= ' Details: ' . $exception->getMessage();
        }

        throw new RuntimeException($message, 0, $exception);
    }
}

function emarioh_ensure_database_exists(PDO $serverDb, string $databaseName): void
{
    $normalizedName = trim($databaseName);

    if ($normalizedName === '') {
        throw new RuntimeException('Database name is required before attempting automatic database creation.');
    }

    $serverDb->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $normalizedName)
    ));
}

function emarioh_required_schema_tables(): array
{
    return [
        'users',
        'otp_codes',
        'remember_tokens',
        'client_profiles',
        'website_inquiries',
        'service_packages',
        'package_pricing_tiers',
        'package_inclusions',
        'package_tags',
        'public_site_settings',
        'public_service_cards',
        'gallery_items',
        'booking_requests',
        'booking_status_logs',
        'client_activity_logs',
        'payment_settings',
        'payment_invoices',
        'payment_receipts',
        'payment_logs',
        'sms_templates',
        'sms_queue',
    ];
}

function emarioh_required_schema_columns(): array
{
    return [
        'public_site_settings' => [
            'service_area',
            'business_hours',
        ],
        'booking_requests' => [
            'package_allows_down_payment',
            'package_down_payment_amount',
        ],
        'package_pricing_tiers' => [
            'down_payment_amount',
        ],
        'payment_invoices' => [
            'gateway_provider',
            'gateway_checkout_session_id',
            'gateway_checkout_reference',
            'gateway_checkout_url',
            'gateway_checkout_status',
            'gateway_payment_id',
            'gateway_payment_intent_id',
            'gateway_paid_at',
        ],
    ];
}

function emarioh_schema_table_exists(PDO $db, string $tableName): bool
{
    $statement = $db->prepare('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = :table_name
    ');
    $statement->execute([
        ':table_name' => $tableName,
    ]);

    return (int) $statement->fetchColumn() > 0;
}

function emarioh_schema_column_exists(PDO $db, string $tableName, string $columnName): bool
{
    $statement = $db->prepare('
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = :table_name
          AND column_name = :column_name
    ');
    $statement->execute([
        ':table_name' => $tableName,
        ':column_name' => $columnName,
    ]);

    return (int) $statement->fetchColumn() > 0;
}

function emarioh_assert_schema_is_ready(PDO $db): void
{
    $missingTables = [];

    foreach (emarioh_required_schema_tables() as $tableName) {
        if (!emarioh_schema_table_exists($db, $tableName)) {
            $missingTables[] = $tableName;
        }
    }

    if ($missingTables !== []) {
        throw new RuntimeException(
            'Database schema is not installed for this environment. '
            . 'Import database/schema/emarioh_catering_db.sql before going live, '
            . 'or temporarily enable database.manage_schema / EMARIOH_DB_MANAGE_SCHEMA '
            . 'for a one-time bootstrap if your database user has CREATE and ALTER privileges. '
            . 'Missing tables: ' . implode(', ', $missingTables) . '.'
        );
    }

    $missingColumns = [];

    foreach (emarioh_required_schema_columns() as $tableName => $columns) {
        foreach ($columns as $columnName) {
            if (!emarioh_schema_column_exists($db, $tableName, $columnName)) {
                $missingColumns[] = $tableName . '.' . $columnName;
            }
        }
    }

    if ($missingColumns !== []) {
        throw new RuntimeException(
            'Database schema is out of date for this environment. '
            . 'Import the latest database/schema/emarioh_catering_db.sql, '
            . 'or temporarily enable database.manage_schema / EMARIOH_DB_MANAGE_SCHEMA '
            . 'for a one-time schema update if your database user has ALTER privileges. '
            . 'Missing columns: ' . implode(', ', $missingColumns) . '.'
        );
    }
}

function emarioh_ensure_schema(PDO $db): void
{
    foreach (emarioh_schema_statements() as $statement) {
        $db->exec($statement);
    }

    emarioh_ensure_public_site_settings_columns($db);
    emarioh_ensure_booking_request_columns($db);
    emarioh_ensure_package_pricing_tier_columns($db);
    emarioh_ensure_payment_invoice_columns($db);
    emarioh_ensure_sms_templates($db);
}

function emarioh_ensure_public_site_settings_columns(PDO $db): void
{
    $columns = $db->query('SHOW COLUMNS FROM public_site_settings')->fetchAll(PDO::FETCH_COLUMN);

    if (!is_array($columns)) {
        return;
    }

    if (!in_array('service_area', $columns, true)) {
        $db->exec('ALTER TABLE public_site_settings ADD COLUMN service_area VARCHAR(255) NULL AFTER messenger_url');
    }

    if (!in_array('business_hours', $columns, true)) {
        $db->exec('ALTER TABLE public_site_settings ADD COLUMN business_hours VARCHAR(190) NULL AFTER service_area');
    }
}

function emarioh_ensure_booking_request_columns(PDO $db): void
{
    $columns = $db->query('SHOW COLUMNS FROM booking_requests')->fetchAll(PDO::FETCH_COLUMN);

    if (!is_array($columns)) {
        return;
    }

    if (!in_array('package_allows_down_payment', $columns, true)) {
        $db->exec('ALTER TABLE booking_requests ADD COLUMN package_allows_down_payment TINYINT(1) NOT NULL DEFAULT 0 AFTER package_tier_price');
    }

    if (!in_array('package_down_payment_amount', $columns, true)) {
        $db->exec('ALTER TABLE booking_requests ADD COLUMN package_down_payment_amount VARCHAR(100) NULL AFTER package_allows_down_payment');
    }
}

function emarioh_ensure_package_pricing_tier_columns(PDO $db): void
{
    $columns = $db->query('SHOW COLUMNS FROM package_pricing_tiers')->fetchAll(PDO::FETCH_COLUMN);

    if (!is_array($columns)) {
        return;
    }

    if (!in_array('down_payment_amount', $columns, true)) {
        $db->exec('ALTER TABLE package_pricing_tiers ADD COLUMN down_payment_amount VARCHAR(100) NULL AFTER price_amount');
    }
}

function emarioh_ensure_payment_invoice_columns(PDO $db): void
{
    $columns = $db->query('SHOW COLUMNS FROM payment_invoices')->fetchAll(PDO::FETCH_COLUMN);

    if (!is_array($columns)) {
        return;
    }

    if (!in_array('gateway_provider', $columns, true)) {
        $db->exec('ALTER TABLE payment_invoices ADD COLUMN gateway_provider VARCHAR(100) NULL AFTER payment_method');
    }

    if (!in_array('gateway_checkout_session_id', $columns, true)) {
        $db->exec('ALTER TABLE payment_invoices ADD COLUMN gateway_checkout_session_id VARCHAR(100) NULL AFTER gateway_provider');
    }

    if (!in_array('gateway_checkout_reference', $columns, true)) {
        $db->exec('ALTER TABLE payment_invoices ADD COLUMN gateway_checkout_reference VARCHAR(120) NULL AFTER gateway_checkout_session_id');
    }

    if (!in_array('gateway_checkout_url', $columns, true)) {
        $db->exec('ALTER TABLE payment_invoices ADD COLUMN gateway_checkout_url TEXT NULL AFTER gateway_checkout_reference');
    }

    if (!in_array('gateway_checkout_status', $columns, true)) {
        $db->exec('ALTER TABLE payment_invoices ADD COLUMN gateway_checkout_status VARCHAR(50) NULL AFTER gateway_checkout_url');
    }

    if (!in_array('gateway_payment_id', $columns, true)) {
        $db->exec('ALTER TABLE payment_invoices ADD COLUMN gateway_payment_id VARCHAR(100) NULL AFTER gateway_checkout_status');
    }

    if (!in_array('gateway_payment_intent_id', $columns, true)) {
        $db->exec('ALTER TABLE payment_invoices ADD COLUMN gateway_payment_intent_id VARCHAR(100) NULL AFTER gateway_payment_id');
    }

    if (!in_array('gateway_paid_at', $columns, true)) {
        $db->exec('ALTER TABLE payment_invoices ADD COLUMN gateway_paid_at DATETIME NULL AFTER gateway_payment_intent_id');
    }
}

function emarioh_ensure_sms_templates(PDO $db): void
{
    static $hasRun = false;

    if ($hasRun) {
        return;
    }

    $hasRun = true;
    $templates = array_values(emarioh_default_sms_templates());

    if ($templates === []) {
        return;
    }

    $statement = $db->prepare('
        INSERT IGNORE INTO sms_templates (
            template_key,
            template_name,
            trigger_label,
            use_case,
            template_body,
            placeholders,
            sort_order,
            is_active
        ) VALUES (
            :template_key,
            :template_name,
            :trigger_label,
            :use_case,
            :template_body,
            :placeholders,
            :sort_order,
            :is_active
        )
    ');

    foreach ($templates as $template) {
        $statement->execute([
            ':template_key' => (string) ($template['template_key'] ?? ''),
            ':template_name' => (string) ($template['template_name'] ?? ''),
            ':trigger_label' => (string) ($template['trigger_label'] ?? ''),
            ':use_case' => emarioh_trim_or_null((string) ($template['use_case'] ?? '')),
            ':template_body' => (string) ($template['template_body'] ?? ''),
            ':placeholders' => emarioh_trim_or_null((string) ($template['placeholders'] ?? '')),
            ':sort_order' => (int) ($template['sort_order'] ?? 0),
            ':is_active' => !empty($template['is_active']) ? 1 : 0,
        ]);
    }
}

function emarioh_schema_statements(): array
{
    return [
        <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    role ENUM('admin', 'client') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    updated_at BIGINT UNSIGNED NOT NULL,
    last_login_at BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_users_mobile (mobile),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS otp_codes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(20) NOT NULL,
    purpose VARCHAR(50) NOT NULL,
    code_hash VARCHAR(64) NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    verified_at BIGINT UNSIGNED NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    KEY idx_otp_codes_mobile_purpose (mobile, purpose, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector VARCHAR(32) NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    created_at BIGINT UNSIGNED NOT NULL,
    last_used_at BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_remember_tokens_selector (selector),
    KEY idx_remember_tokens_user (user_id),
    CONSTRAINT fk_remember_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS client_profiles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(190) NULL,
    alternate_contact VARCHAR(190) NULL,
    preferred_contact VARCHAR(100) NULL,
    notes TEXT NULL,
    last_activity_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_client_profiles_user (user_id),
    KEY idx_client_profiles_email (email),
    CONSTRAINT fk_client_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS website_inquiries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(40) NOT NULL,
    user_id INT UNSIGNED NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    mobile VARCHAR(20) NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'General Inquiry',
    source VARCHAR(100) NOT NULL DEFAULT 'Public Website',
    subject VARCHAR(190) NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'archived') NOT NULL DEFAULT 'unread',
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_website_inquiries_reference (reference),
    KEY idx_website_inquiries_user (user_id),
    KEY idx_website_inquiries_status (status),
    KEY idx_website_inquiries_submitted (submitted_at),
    CONSTRAINT fk_website_inquiries_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS service_packages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(100) NOT NULL,
    group_key ENUM('per-head', 'celebration') NOT NULL DEFAULT 'per-head',
    name VARCHAR(190) NOT NULL,
    category_label VARCHAR(120) NOT NULL,
    guest_label VARCHAR(120) NOT NULL,
    rate_label VARCHAR(120) NOT NULL,
    description TEXT NULL,
    status ENUM('active', 'review', 'inactive') NOT NULL DEFAULT 'review',
    allow_down_payment TINYINT(1) NOT NULL DEFAULT 0,
    down_payment_amount VARCHAR(100) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_service_packages_code (package_code),
    KEY idx_service_packages_group_status (group_key, status),
    KEY idx_service_packages_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS package_pricing_tiers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    package_id BIGINT UNSIGNED NOT NULL,
    tier_label VARCHAR(100) NOT NULL,
    guest_count INT UNSIGNED NULL,
    price_label VARCHAR(100) NOT NULL,
    price_amount DECIMAL(12,2) NULL,
    down_payment_amount VARCHAR(100) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_package_pricing_tiers_package (package_id),
    KEY idx_package_pricing_tiers_sort_order (sort_order),
    CONSTRAINT fk_package_pricing_tiers_package
        FOREIGN KEY (package_id) REFERENCES service_packages(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS package_inclusions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    package_id BIGINT UNSIGNED NOT NULL,
    inclusion_text VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_package_inclusions_package (package_id),
    KEY idx_package_inclusions_sort_order (sort_order),
    CONSTRAINT fk_package_inclusions_package
        FOREIGN KEY (package_id) REFERENCES service_packages(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS package_tags (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    package_id BIGINT UNSIGNED NOT NULL,
    tag_text VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_package_tags_package (package_id),
    KEY idx_package_tags_sort_order (sort_order),
    CONSTRAINT fk_package_tags_package
        FOREIGN KEY (package_id) REFERENCES service_packages(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS public_site_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    hero_image_path VARCHAR(255) NULL,
    hero_image_alt VARCHAR(190) NULL,
    primary_mobile VARCHAR(20) NULL,
    secondary_mobile VARCHAR(20) NULL,
    public_email VARCHAR(190) NULL,
    inquiry_email VARCHAR(190) NULL,
    facebook_url VARCHAR(255) NULL,
    messenger_url VARCHAR(255) NULL,
    service_area VARCHAR(255) NULL,
    business_hours VARCHAR(190) NULL,
    business_address VARCHAR(255) NULL,
    map_embed_url TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS public_service_cards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slot_key VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    image_alt VARCHAR(190) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_public_service_cards_slot (slot_key),
    KEY idx_public_service_cards_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS gallery_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NULL,
    image_path VARCHAR(255) NULL,
    image_alt VARCHAR(190) NULL,
    placement_label VARCHAR(100) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_gallery_items_category_status (category, status),
    KEY idx_gallery_items_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS booking_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(40) NOT NULL,
    user_id INT UNSIGNED NULL,
    inquiry_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    guest_count INT UNSIGNED NOT NULL,
    venue_option ENUM('own', 'emarioh') NOT NULL DEFAULT 'own',
    venue_name VARCHAR(255) NOT NULL,
    package_category_value VARCHAR(100) NULL,
    package_selection_value VARCHAR(150) NULL,
    package_label VARCHAR(190) NULL,
    package_id BIGINT UNSIGNED NULL,
    package_tier_label VARCHAR(100) NULL,
    package_tier_price VARCHAR(100) NULL,
    package_allows_down_payment TINYINT(1) NOT NULL DEFAULT 0,
    package_down_payment_amount VARCHAR(100) NULL,
    primary_contact VARCHAR(150) NOT NULL,
    primary_mobile VARCHAR(20) NOT NULL,
    primary_email VARCHAR(190) NOT NULL,
    alternate_contact VARCHAR(190) NULL,
    event_notes TEXT NULL,
    admin_notes TEXT NULL,
    status ENUM('pending_review', 'approved', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending_review',
    booking_source VARCHAR(50) NOT NULL DEFAULT 'client_portal',
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    approved_at DATETIME NULL,
    rejected_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    completed_at DATETIME NULL,
    reviewed_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_booking_requests_reference (reference),
    KEY idx_booking_requests_user (user_id),
    KEY idx_booking_requests_inquiry (inquiry_id),
    KEY idx_booking_requests_package (package_id),
    KEY idx_booking_requests_status (status),
    KEY idx_booking_requests_event_date (event_date),
    KEY idx_booking_requests_reviewer (reviewed_by_user_id),
    CONSTRAINT fk_booking_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_booking_requests_inquiry
        FOREIGN KEY (inquiry_id) REFERENCES website_inquiries(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_booking_requests_package
        FOREIGN KEY (package_id) REFERENCES service_packages(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_booking_requests_reviewer
        FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS booking_status_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    changed_by_user_id INT UNSIGNED NULL,
    from_status VARCHAR(50) NULL,
    to_status VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    summary VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_booking_status_logs_booking (booking_id),
    KEY idx_booking_status_logs_actor (changed_by_user_id),
    CONSTRAINT fk_booking_status_logs_booking
        FOREIGN KEY (booking_id) REFERENCES booking_requests(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_booking_status_logs_actor
        FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS client_activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    title VARCHAR(150) NOT NULL,
    details TEXT NULL,
    related_reference VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_client_activity_logs_user (user_id),
    KEY idx_client_activity_logs_created (created_at),
    CONSTRAINT fk_client_activity_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS payment_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    payment_gateway VARCHAR(100) NOT NULL DEFAULT 'PayMongo',
    active_method VARCHAR(50) NOT NULL DEFAULT 'PayMongo QRPh',
    accepted_wallets_label VARCHAR(190) NOT NULL DEFAULT 'Any QRPh-supported e-wallet or banking app',
    allow_full_payment TINYINT(1) NOT NULL DEFAULT 1,
    balance_due_rule VARCHAR(100) NOT NULL DEFAULT '3 days before event',
    receipt_requirement ENUM('receipt_required', 'any_proof') NOT NULL DEFAULT 'receipt_required',
    confirmation_rule ENUM('verified_down_payment', 'manual_review') NOT NULL DEFAULT 'verified_down_payment',
    support_mobile VARCHAR(20) NULL,
    instruction_text TEXT NULL,
    updated_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_payment_settings_updated_by (updated_by_user_id),
    CONSTRAINT fk_payment_settings_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS payment_invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL,
    booking_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    invoice_type ENUM('down_payment', 'progress_payment', 'final_balance', 'full_payment', 'adjustment') NOT NULL DEFAULT 'down_payment',
    title VARCHAR(190) NOT NULL,
    description VARCHAR(255) NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'PayMongo QRPh',
    currency_code CHAR(3) NOT NULL DEFAULT 'PHP',
    amount_due DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    due_date DATE NULL,
    status ENUM('pending', 'review', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    stage_label VARCHAR(120) NULL,
    note_text TEXT NULL,
    last_payment_at DATETIME NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payment_invoices_number (invoice_number),
    KEY idx_payment_invoices_booking (booking_id),
    KEY idx_payment_invoices_user (user_id),
    KEY idx_payment_invoices_status (status),
    KEY idx_payment_invoices_due_date (due_date),
    KEY idx_payment_invoices_creator (created_by_user_id),
    CONSTRAINT fk_payment_invoices_booking
        FOREIGN KEY (booking_id) REFERENCES booking_requests(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payment_invoices_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_payment_invoices_creator
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS payment_receipts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    booking_id BIGINT UNSIGNED NOT NULL,
    uploaded_by_user_id INT UNSIGNED NULL,
    original_file_name VARCHAR(255) NULL,
    stored_file_path VARCHAR(255) NULL,
    receipt_reference VARCHAR(100) NULL,
    sender_name VARCHAR(150) NULL,
    sender_mobile VARCHAR(20) NULL,
    amount_reported DECIMAL(12,2) NULL,
    notes TEXT NULL,
    status ENUM('uploaded', 'review', 'verified', 'rejected') NOT NULL DEFAULT 'uploaded',
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    reviewed_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_payment_receipts_invoice (invoice_id),
    KEY idx_payment_receipts_booking (booking_id),
    KEY idx_payment_receipts_status (status),
    KEY idx_payment_receipts_uploaded_by (uploaded_by_user_id),
    KEY idx_payment_receipts_reviewed_by (reviewed_by_user_id),
    CONSTRAINT fk_payment_receipts_invoice
        FOREIGN KEY (invoice_id) REFERENCES payment_invoices(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payment_receipts_booking
        FOREIGN KEY (booking_id) REFERENCES booking_requests(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payment_receipts_uploaded_by
        FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_payment_receipts_reviewed_by
        FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS payment_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    booking_id BIGINT UNSIGNED NOT NULL,
    actor_user_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    summary VARCHAR(255) NULL,
    meta_label VARCHAR(255) NULL,
    amount_label VARCHAR(100) NULL,
    status_class VARCHAR(50) NULL,
    status_label VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_payment_logs_invoice (invoice_id),
    KEY idx_payment_logs_booking (booking_id),
    KEY idx_payment_logs_actor (actor_user_id),
    CONSTRAINT fk_payment_logs_invoice
        FOREIGN KEY (invoice_id) REFERENCES payment_invoices(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payment_logs_booking
        FOREIGN KEY (booking_id) REFERENCES booking_requests(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payment_logs_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS sms_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL,
    template_name VARCHAR(150) NOT NULL,
    trigger_label VARCHAR(150) NOT NULL,
    use_case VARCHAR(190) NULL,
    template_body TEXT NOT NULL,
    placeholders VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sms_templates_key (template_key),
    KEY idx_sms_templates_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        <<<'SQL'
CREATE TABLE IF NOT EXISTS sms_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    template_id BIGINT UNSIGNED NULL,
    booking_id BIGINT UNSIGNED NULL,
    inquiry_id BIGINT UNSIGNED NULL,
    recipient_name VARCHAR(150) NOT NULL,
    recipient_mobile VARCHAR(20) NOT NULL,
    trigger_label VARCHAR(150) NOT NULL,
    message_body TEXT NOT NULL,
    source_label VARCHAR(100) NULL,
    provider_name VARCHAR(100) NULL,
    provider_message_id VARCHAR(150) NULL,
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    failed_at DATETIME NULL,
    status ENUM('queued', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'queued',
    failure_reason TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sms_queue_template (template_id),
    KEY idx_sms_queue_booking (booking_id),
    KEY idx_sms_queue_inquiry (inquiry_id),
    KEY idx_sms_queue_status (status),
    KEY idx_sms_queue_schedule (scheduled_at),
    CONSTRAINT fk_sms_queue_template
        FOREIGN KEY (template_id) REFERENCES sms_templates(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_sms_queue_booking
        FOREIGN KEY (booking_id) REFERENCES booking_requests(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_sms_queue_inquiry
        FOREIGN KEY (inquiry_id) REFERENCES website_inquiries(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    ];
}

function emarioh_cleanup_expired_records(PDO $db): void
{
    $now = time();
    $dayAgo = $now - 86400;

    $db->prepare('DELETE FROM otp_codes WHERE expires_at < :now OR (verified_at IS NOT NULL AND verified_at < :day_ago)')
        ->execute([
            ':now' => $now,
            ':day_ago' => $dayAgo,
        ]);

    $db->prepare('DELETE FROM remember_tokens WHERE expires_at < :now')
        ->execute([
            ':now' => $now,
        ]);
}

function emarioh_require_method(string $method): void
{
    $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($requestMethod !== strtoupper($method)) {
        emarioh_fail('Method not allowed.', 405);
    }
}

function emarioh_request_data(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

    if (str_contains($contentType, 'application/json')) {
        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (JsonException $exception) {
            return [];
        }
    }

    parse_str($rawBody, $parsedBody);
    return is_array($parsedBody) ? $parsedBody : [];
}

function emarioh_json_response(array $payload, int $statusCode = 200): never
{
    $payload = emarioh_normalize_json_response_payload($payload);
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function emarioh_success(array $payload = [], int $statusCode = 200): never
{
    emarioh_json_response(array_merge(['ok' => true], $payload), $statusCode);
}

function emarioh_fail(string $message, int $statusCode = 422, array $payload = []): never
{
    emarioh_json_response(array_merge([
        'ok' => false,
        'message' => $message,
    ], $payload), $statusCode);
}

function emarioh_normalize_name(string $value): string
{
    $normalized = preg_replace('/\s+/', ' ', trim($value));
    return $normalized === null ? '' : $normalized;
}

function emarioh_normalize_mobile(string $value): string
{
    $digits = preg_replace('/\D+/', '', $value) ?? '';

    if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
        return '63' . $digits;
    }

    if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        return '63' . substr($digits, 1);
    }

    return $digits;
}

function emarioh_is_valid_mobile(string $mobile): bool
{
    return preg_match('/^639\d{9}$/', $mobile) === 1;
}

function emarioh_format_mobile(string $mobile): string
{
    if (!emarioh_is_valid_mobile($mobile)) {
        return $mobile;
    }

    $localMobile = '0' . substr($mobile, 2);
    return substr($localMobile, 0, 4) . ' ' . substr($localMobile, 4, 3) . ' ' . substr($localMobile, 7);
}

function emarioh_mask_mobile(string $mobile): string
{
    if (!emarioh_is_valid_mobile($mobile)) {
        return $mobile;
    }

    $localMobile = '0' . substr($mobile, 2);
    return substr($localMobile, 0, 4) . ' *** ' . substr($localMobile, -4);
}

function emarioh_first_name(string $fullName): string
{
    $normalizedName = emarioh_normalize_name($fullName);

    if ($normalizedName === '') {
        return 'User';
    }

    $nameParts = preg_split('/\s+/', $normalizedName);
    $firstName = $nameParts[0] ?? 'User';

    return ucfirst(strtolower($firstName));
}

function emarioh_admin_exists(PDO $db): bool
{
    $statement = $db->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1");
    return (bool) $statement->fetchColumn();
}

function emarioh_find_user_by_mobile(PDO $db, string $mobile): ?array
{
    $statement = $db->prepare('SELECT * FROM users WHERE mobile = :mobile LIMIT 1');
    $statement->execute([
        ':mobile' => $mobile,
    ]);

    $user = $statement->fetch();
    return is_array($user) ? $user : null;
}

function emarioh_find_user_by_id(PDO $db, int $userId): ?array
{
    $statement = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $statement->execute([
        ':id' => $userId,
    ]);

    $user = $statement->fetch();
    return is_array($user) ? $user : null;
}

function emarioh_public_user(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'first_name' => emarioh_first_name((string) $user['full_name']),
        'mobile' => emarioh_format_mobile((string) $user['mobile']),
        'mobile_masked' => emarioh_mask_mobile((string) $user['mobile']),
        'role' => (string) $user['role'],
    ];
}

function emarioh_role_landing_url(string $role): string
{
    return $role === 'admin' ? 'index.php' : 'client-dashboard.php';
}

function emarioh_admin_mobile_nav_items(): array
{
    return [
        [
            'href' => 'index.php',
            'icon' => 'bi-grid-1x2-fill',
            'label' => 'Home',
        ],
        [
            'href' => 'admin-bookings.php',
            'icon' => 'bi-journal-check',
            'label' => 'Bookings',
        ],
        [
            'href' => 'admin-events.php',
            'icon' => 'bi-calendar-event',
            'label' => 'Events',
        ],
        [
            'href' => 'admin-payments.php',
            'icon' => 'bi-wallet2',
            'label' => 'Payments',
        ],
        [
            'href' => 'admin-settings.php',
            'icon' => 'bi-person-circle',
            'label' => 'Profile',
            'active_paths' => [
                'admin-settings.php',
                'admin-clients.php',
                'admin-inquiries.php',
            ],
        ],
    ];
}

function emarioh_client_mobile_nav_items(): array
{
    return [
        [
            'href' => 'client-dashboard.php',
            'icon' => 'bi-grid-1x2-fill',
            'label' => 'Home',
        ],
        [
            'href' => 'client-bookings.php',
            'icon' => 'bi-calendar2-plus',
            'label' => 'Book',
        ],
        [
            'href' => 'client-my-bookings.php',
            'icon' => 'bi-journal-check',
            'label' => 'Bookings',
        ],
        [
            'href' => 'client-billing.php',
            'icon' => 'bi-wallet2',
            'label' => 'Billing',
        ],
        [
            'href' => 'client-preferences.php',
            'icon' => 'bi-person-circle',
            'label' => 'Profile',
        ],
    ];
}

function emarioh_render_admin_mobile_nav(string $currentPath): string
{
    $normalizedCurrentPath = trim(basename($currentPath)) ?: 'index.php';
    $markup = '<nav class="admin-mobile-bottom-nav d-xl-none" aria-label="Admin quick navigation">';

    foreach (emarioh_admin_mobile_nav_items() as $item) {
        $href = trim((string) ($item['href'] ?? ''));

        if ($href === '') {
            continue;
        }

        $label = trim((string) ($item['label'] ?? '')) ?: 'Open';
        $icon = trim((string) ($item['icon'] ?? ''));
        $mark = trim((string) ($item['mark'] ?? '')) ?: strtoupper(substr($label, 0, 1));
        $activePaths = array_values(array_filter(array_map(
            static fn ($path): string => trim((string) $path),
            is_array($item['active_paths'] ?? null) ? $item['active_paths'] : []
        )));

        if ($activePaths === []) {
            $activePaths[] = basename($href);
        }

        $isActive = in_array($normalizedCurrentPath, $activePaths, true);
        $className = 'admin-mobile-bottom-nav__item' . ($isActive ? ' is-active' : '');

        $markup .= '<a class="'
            . htmlspecialchars($className, ENT_QUOTES, 'UTF-8')
            . '" href="'
            . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
            . '"'
            . ($isActive ? ' aria-current="page"' : '')
            . '>';

        $markup .= '<span class="admin-mobile-bottom-nav__icon" aria-hidden="true">';

        if ($icon !== '') {
            $markup .= '<i class="bi ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i>';
        } else {
            $markup .= htmlspecialchars($mark, ENT_QUOTES, 'UTF-8');
        }

        $markup .= '</span>';
        $markup .= '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></a>';
    }

    $markup .= '</nav>';

    return $markup;
}

function emarioh_render_client_mobile_nav(string $currentPath): string
{
    $normalizedCurrentPath = trim(basename($currentPath)) ?: 'client-dashboard.php';
    $markup = '<nav class="client-mobile-bottom-nav d-xl-none" aria-label="Client quick navigation">';

    foreach (emarioh_client_mobile_nav_items() as $item) {
        $href = trim((string) ($item['href'] ?? ''));

        if ($href === '') {
            continue;
        }

        $label = trim((string) ($item['label'] ?? '')) ?: 'Open';
        $icon = trim((string) ($item['icon'] ?? ''));
        $mark = trim((string) ($item['mark'] ?? '')) ?: strtoupper(substr($label, 0, 1));
        $activePaths = array_values(array_filter(array_map(
            static fn ($path): string => trim((string) $path),
            is_array($item['active_paths'] ?? null) ? $item['active_paths'] : []
        )));

        if ($activePaths === []) {
            $activePaths[] = basename($href);
        }

        $isActive = in_array($normalizedCurrentPath, $activePaths, true);
        $className = 'client-mobile-bottom-nav__item' . ($isActive ? ' is-active' : '');

        $markup .= '<a class="'
            . htmlspecialchars($className, ENT_QUOTES, 'UTF-8')
            . '" href="'
            . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
            . '"'
            . ($isActive ? ' aria-current="page"' : '')
            . '>';

        if ($icon !== '') {
            $markup .= '<i class="bi ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
        } else {
            $markup .= '<span aria-hidden="true">' . htmlspecialchars($mark, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        $markup .= '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></a>';
    }

    $markup .= '</nav>';

    return $markup;
}

function emarioh_generate_otp(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function emarioh_create_otp(PDO $db, string $mobile, string $purpose): string
{
    $otp = emarioh_generate_otp();
    $now = time();

    emarioh_clear_verified_otp($mobile, $purpose);
    emarioh_delete_otp($db, $mobile, $purpose);

    $db->prepare('
        INSERT INTO otp_codes (mobile, purpose, code_hash, expires_at, created_at)
        VALUES (:mobile, :purpose, :code_hash, :expires_at, :created_at)
    ')->execute([
        ':mobile' => $mobile,
        ':purpose' => $purpose,
        ':code_hash' => hash('sha256', $otp),
        ':expires_at' => $now + EMARIOH_OTP_TTL,
        ':created_at' => $now,
    ]);

    return $otp;
}

function emarioh_delete_otp(PDO $db, string $mobile, string $purpose): void
{
    $db->prepare('DELETE FROM otp_codes WHERE mobile = :mobile AND purpose = :purpose')
        ->execute([
            ':mobile' => $mobile,
            ':purpose' => $purpose,
        ]);
}

function emarioh_find_latest_otp(PDO $db, string $mobile, string $purpose): ?array
{
    $statement = $db->prepare('
        SELECT *
        FROM otp_codes
        WHERE mobile = :mobile AND purpose = :purpose
        ORDER BY id DESC
        LIMIT 1
    ');
    $statement->execute([
        ':mobile' => $mobile,
        ':purpose' => $purpose,
    ]);

    $otpRecord = $statement->fetch();
    return is_array($otpRecord) ? $otpRecord : null;
}

function emarioh_store_verified_otp(string $mobile, string $purpose): void
{
    $key = $purpose . '|' . $mobile;
    $_SESSION['verified_otp'][$key] = time() + EMARIOH_OTP_VERIFIED_TTL;
}

function emarioh_has_verified_otp(string $mobile, string $purpose): bool
{
    $key = $purpose . '|' . $mobile;
    $expiresAt = (int) ($_SESSION['verified_otp'][$key] ?? 0);

    if ($expiresAt < time()) {
        unset($_SESSION['verified_otp'][$key]);
        return false;
    }

    return true;
}

function emarioh_clear_verified_otp(string $mobile, string $purpose): void
{
    $key = $purpose . '|' . $mobile;
    unset($_SESSION['verified_otp'][$key]);
}

function emarioh_sms_gateway_is_enabled(): bool
{
    $smsConfig = emarioh_config()['sms'] ?? [];

    if (!is_array($smsConfig) || !($smsConfig['enabled'] ?? false)) {
        return false;
    }

    return emarioh_sms_provider_is_ready($smsConfig);
}

function emarioh_sms_provider_is_ready(array $smsConfig): bool
{
    if (strtolower((string) ($smsConfig['provider'] ?? '')) !== 'semaphore') {
        return false;
    }

    $semaphoreConfig = $smsConfig['semaphore'] ?? [];

    if (!is_array($semaphoreConfig)) {
        return false;
    }

    return trim((string) ($semaphoreConfig['api_key'] ?? '')) !== '';
}

function emarioh_booking_status_sms_is_enabled(): bool
{
    $smsConfig = emarioh_config()['sms'] ?? [];

    if (!is_array($smsConfig) || !emarioh_sms_provider_is_ready($smsConfig)) {
        return false;
    }

    return array_key_exists('booking_status_enabled', $smsConfig)
        ? (bool) $smsConfig['booking_status_enabled']
        : (bool) ($smsConfig['enabled'] ?? false);
}

function emarioh_booking_sms_notifications_are_enabled(): bool
{
    return emarioh_booking_status_sms_is_enabled();
}

function emarioh_default_sms_templates(): array
{
    return [
        'booking_received' => [
            'template_key' => 'booking_received',
            'template_name' => 'Booking Received',
            'trigger_label' => 'Booking received',
            'use_case' => 'Sent after a client submits a new booking request.',
            'template_body' => 'Emarioh Catering: Hi [Client Name], we received your booking request. We are now checking availability and will update you soon. Ref: [Booking Ref].',
            'placeholders' => '[Client Name], [Booking Ref], [Event Date], [Event Time], [Event Type], [Event Schedule]',
            'sort_order' => 20,
            'is_active' => true,
        ],
        'booking_approved' => [
            'template_key' => 'booking_approved',
            'template_name' => 'Booking Approved',
            'trigger_label' => 'Booking approved',
            'use_case' => 'Sent after the admin approves a booking request.',
            'template_body' => 'Emarioh Catering: Hi [Client Name], your booking for [Event Date] has been approved. Please settle the required down payment using your preferred e-wallet to confirm your reservation. Ref: [Booking Ref].',
            'placeholders' => '[Client Name], [Booking Ref], [Event Date], [Event Time], [Event Type], [Event Schedule]',
            'sort_order' => 30,
            'is_active' => true,
        ],
        'booking_rejected' => [
            'template_key' => 'booking_rejected',
            'template_name' => 'Booking Rejected',
            'trigger_label' => 'Booking rejected',
            'use_case' => 'Sent after the admin rejects a booking request.',
            'template_body' => 'Emarioh Catering: Hi [Client Name], your booking [Booking Ref] for [Event Date] was not approved. Please review your client portal or contact us for assistance.',
            'placeholders' => '[Client Name], [Booking Ref], [Event Date], [Event Time], [Event Type], [Event Schedule]',
            'sort_order' => 40,
            'is_active' => true,
        ],
        'downpayment_reminder' => [
            'template_key' => 'downpayment_reminder',
            'template_name' => 'Down Payment Reminder',
            'trigger_label' => 'Down payment reminder',
            'use_case' => 'Sent by the admin when the approved booking still has no posted down payment.',
            'template_body' => 'Emarioh Catering: Hi [Client Name], this is a reminder that your down payment for [Booking Ref] is still pending. Please complete the payment using your preferred e-wallet through your Billing page and refresh the latest status after checkout.',
            'placeholders' => '[Client Name], [Booking Ref], [Event Date], [Event Time], [Event Type], [Event Schedule]',
            'sort_order' => 50,
            'is_active' => true,
        ],
        'payment_verified' => [
            'template_key' => 'payment_verified',
            'template_name' => 'Payment Verified',
            'trigger_label' => 'Payment verified',
            'use_case' => 'Sent after the system confirms a client payment.',
            'template_body' => 'Emarioh Catering: Hi [Client Name], your payment for booking [Booking Ref] has been verified. Thank you, and we will keep you posted on the next update.',
            'placeholders' => '[Client Name], [Booking Ref], [Event Date], [Event Time], [Event Type], [Event Schedule]',
            'sort_order' => 60,
            'is_active' => true,
        ],
        'final_event_reminder' => [
            'template_key' => 'final_event_reminder',
            'template_name' => 'Final Event Reminder',
            'trigger_label' => 'Final event reminder',
            'use_case' => 'Sent by the admin before the scheduled event date for final coordination.',
            'template_body' => 'Emarioh Catering: Hi [Client Name], this is your event reminder for [Event Date]. Please keep your line open for final coordination. Thank you.',
            'placeholders' => '[Client Name], [Booking Ref], [Event Date], [Event Time], [Event Type], [Event Schedule]',
            'sort_order' => 70,
            'is_active' => true,
        ],
    ];
}

function emarioh_default_sms_template(string $templateKey): ?array
{
    $templates = emarioh_default_sms_templates();
    return $templates[$templateKey] ?? null;
}

function emarioh_fetch_sms_templates(PDO $db, array $templateKeys = []): array
{
    emarioh_ensure_sms_templates($db);

    $defaultTemplates = emarioh_default_sms_templates();
    $normalizedKeys = $templateKeys === []
        ? array_keys($defaultTemplates)
        : array_values(array_filter(
            array_map(
                static fn ($key): string => trim((string) $key),
                $templateKeys
            ),
            static fn (string $key): bool => $key !== '' && array_key_exists($key, $defaultTemplates)
        ));

    if ($normalizedKeys === []) {
        return [];
    }

    $queryPlaceholders = [];
    $queryParams = [];

    foreach ($normalizedKeys as $index => $templateKey) {
        $placeholder = ':template_key_' . $index;
        $queryPlaceholders[] = $placeholder;
        $queryParams[$placeholder] = $templateKey;
    }

    $statement = $db->prepare(sprintf(
        'SELECT * FROM sms_templates WHERE template_key IN (%s) ORDER BY sort_order ASC, id ASC',
        implode(', ', $queryPlaceholders)
    ));
    $statement->execute($queryParams);

    $templatesByKey = [];

    foreach ($statement->fetchAll() as $row) {
        if (!is_array($row)) {
            continue;
        }

        $templateKey = trim((string) ($row['template_key'] ?? ''));

        if ($templateKey === '') {
            continue;
        }

        $templatesByKey[$templateKey] = array_merge(
            $defaultTemplates[$templateKey] ?? [],
            $row
        );
    }

    $orderedTemplates = [];

    foreach ($normalizedKeys as $templateKey) {
        if (isset($templatesByKey[$templateKey])) {
            $orderedTemplates[$templateKey] = $templatesByKey[$templateKey];
            continue;
        }

        if (isset($defaultTemplates[$templateKey])) {
            $orderedTemplates[$templateKey] = $defaultTemplates[$templateKey];
        }
    }

    return $orderedTemplates;
}

function emarioh_save_sms_templates(PDO $db, array $templateBodies): array
{
    $defaultTemplates = emarioh_default_sms_templates();

    if ($defaultTemplates === []) {
        return [];
    }

    $currentTemplates = emarioh_fetch_sms_templates($db);
    $statement = $db->prepare('
        INSERT INTO sms_templates (
            template_key,
            template_name,
            trigger_label,
            use_case,
            template_body,
            placeholders,
            sort_order,
            is_active
        ) VALUES (
            :template_key,
            :template_name,
            :trigger_label,
            :use_case,
            :template_body,
            :placeholders,
            :sort_order,
            :is_active
        )
        ON DUPLICATE KEY UPDATE
            template_name = VALUES(template_name),
            trigger_label = VALUES(trigger_label),
            use_case = VALUES(use_case),
            template_body = VALUES(template_body),
            placeholders = VALUES(placeholders),
            sort_order = VALUES(sort_order),
            is_active = VALUES(is_active)
    ');

    $db->beginTransaction();

    try {
        foreach ($defaultTemplates as $templateKey => $defaultTemplate) {
            if (!array_key_exists($templateKey, $templateBodies)) {
                continue;
            }

            $currentTemplate = $currentTemplates[$templateKey] ?? $defaultTemplate;
            $templateBody = trim((string) $templateBodies[$templateKey]);

            $statement->execute([
                ':template_key' => $templateKey,
                ':template_name' => (string) ($defaultTemplate['template_name'] ?? $currentTemplate['template_name'] ?? $templateKey),
                ':trigger_label' => (string) ($defaultTemplate['trigger_label'] ?? $currentTemplate['trigger_label'] ?? $templateKey),
                ':use_case' => emarioh_trim_or_null((string) ($defaultTemplate['use_case'] ?? $currentTemplate['use_case'] ?? '')),
                ':template_body' => $templateBody,
                ':placeholders' => emarioh_trim_or_null((string) ($defaultTemplate['placeholders'] ?? $currentTemplate['placeholders'] ?? '')),
                ':sort_order' => (int) ($defaultTemplate['sort_order'] ?? $currentTemplate['sort_order'] ?? 0),
                ':is_active' => !empty($currentTemplate['is_active']) ? 1 : (!empty($defaultTemplate['is_active']) ? 1 : 0),
            ]);
        }

        $db->commit();
    } catch (Throwable $throwable) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        throw $throwable;
    }

    return emarioh_fetch_sms_templates($db);
}

function emarioh_find_sms_template_by_key(PDO $db, string $templateKey): ?array
{
    $normalizedKey = trim($templateKey);

    if ($normalizedKey === '') {
        return null;
    }

    emarioh_ensure_sms_templates($db);

    $statement = $db->prepare('
        SELECT *
        FROM sms_templates
        WHERE template_key = :template_key
        LIMIT 1
    ');
    $statement->execute([
        ':template_key' => $normalizedKey,
    ]);

    $template = $statement->fetch();

    if (is_array($template)) {
        return $template;
    }

    return emarioh_default_sms_template($normalizedKey);
}

function emarioh_render_sms_template_body(string $templateBody, array $placeholderValues = []): string
{
    if ($templateBody === '') {
        return '';
    }

    $replacements = [];

    foreach ($placeholderValues as $placeholder => $value) {
        $normalizedPlaceholder = trim((string) $placeholder);

        if ($normalizedPlaceholder === '') {
            continue;
        }

        $replacementValue = trim((string) $value);

        if (!str_starts_with($normalizedPlaceholder, '[')) {
            $normalizedPlaceholder = '[' . $normalizedPlaceholder;
        }

        if (!str_ends_with($normalizedPlaceholder, ']')) {
            $normalizedPlaceholder .= ']';
        }

        $replacements[$normalizedPlaceholder] = $replacementValue;
    }

    return trim(strtr($templateBody, $replacements));
}

function emarioh_send_sms_message(PDO $db, string $recipientName, string $mobile, string $message, array $options = []): array
{
    $smsConfig = emarioh_config()['sms'] ?? [];
    $semaphoreConfig = is_array($smsConfig['semaphore'] ?? null) ? $smsConfig['semaphore'] : [];
    $apiKey = trim((string) ($semaphoreConfig['api_key'] ?? ''));
    $endpoint = trim((string) ($options['endpoint'] ?? ($semaphoreConfig['messages_endpoint'] ?? 'https://api.semaphore.co/api/v4/messages')));
    $senderName = trim((string) ($semaphoreConfig['sender_name'] ?? ''));
    $timeoutSeconds = max(5, (int) ($semaphoreConfig['timeout_seconds'] ?? 15));
    $actualMessage = trim(preg_replace('/\s+/', ' ', $message) ?? '');
    $logEntry = [
        'template_id' => (int) ($options['template_id'] ?? 0),
        'booking_id' => (int) ($options['booking_id'] ?? 0),
        'inquiry_id' => (int) ($options['inquiry_id'] ?? 0),
        'scheduled_at' => $options['scheduled_at'] ?? null,
        'recipient_name' => $recipientName,
        'recipient_mobile' => $mobile,
        'trigger_label' => (string) ($options['trigger_label'] ?? 'SMS Notification'),
        'message_body' => $actualMessage,
        'source_label' => (string) ($options['source_label'] ?? 'System'),
        'provider_name' => 'Semaphore',
    ];

    if (!emarioh_sms_provider_is_ready(is_array($smsConfig) ? $smsConfig : [])) {
        return [
            'ok' => false,
            'message' => 'SMS gateway is not configured.',
            'skipped' => true,
        ];
    }

    if (!emarioh_is_valid_mobile($mobile)) {
        emarioh_log_sms_queue($db, array_merge($logEntry, [
            'status' => 'failed',
            'failure_reason' => 'Recipient mobile number is invalid.',
        ]));

        return [
            'ok' => false,
            'message' => 'SMS recipient mobile number is invalid.',
        ];
    }

    if ($actualMessage === '') {
        emarioh_log_sms_queue($db, array_merge($logEntry, [
            'status' => 'failed',
            'failure_reason' => 'SMS message body is empty.',
        ]));

        return [
            'ok' => false,
            'message' => 'SMS message body is empty.',
        ];
    }

    $payload = [
        'apikey' => $apiKey,
        'number' => $mobile,
        'message' => $actualMessage,
    ];

    if ($senderName !== '') {
        $payload['sendername'] = $senderName;
    }

    $httpResult = emarioh_http_post_form($endpoint, $payload, $timeoutSeconds);

    if ($httpResult['error'] !== null) {
        emarioh_log_sms_queue($db, array_merge($logEntry, [
            'provider_message_id' => null,
            'status' => 'failed',
            'failure_reason' => $httpResult['error'],
        ]));

        return [
            'ok' => false,
            'message' => 'SMS could not be sent right now. Please try again in a moment.',
        ];
    }

    if ($httpResult['status_code'] >= 400) {
        $failureReason = 'Semaphore returned HTTP ' . $httpResult['status_code'];
        $responseSummary = emarioh_summarize_http_body($httpResult['body']);

        if ($responseSummary !== '') {
            $failureReason .= ': ' . $responseSummary;
        }

        emarioh_log_sms_queue($db, array_merge($logEntry, [
            'provider_message_id' => null,
            'status' => 'failed',
            'failure_reason' => $failureReason,
        ]));

        return [
            'ok' => false,
            'message' => 'SMS could not be sent right now. Please try again in a moment.',
        ];
    }

    $decoded = json_decode($httpResult['body'], true);
    $providerMessage = is_array($decoded[0] ?? null) ? $decoded[0] : null;

    if (!is_array($providerMessage)) {
        $failureReason = 'Unexpected Semaphore response.';
        $responseSummary = emarioh_summarize_http_body($httpResult['body']);

        if ($responseSummary !== '') {
            $failureReason .= ' ' . $responseSummary;
        }

        emarioh_log_sms_queue($db, array_merge($logEntry, [
            'provider_message_id' => null,
            'status' => 'failed',
            'failure_reason' => $failureReason,
        ]));

        return [
            'ok' => false,
            'message' => 'SMS could not be sent right now. Please try again in a moment.',
        ];
    }

    $providerStatus = (string) ($providerMessage['status'] ?? 'Queued');
    $queueStatus = emarioh_map_sms_queue_status($providerStatus);
    $providerMessageBody = trim((string) ($providerMessage['message'] ?? ''));
    $providerMessageId = (string) ($providerMessage['message_id'] ?? '');
    $failureReason = null;

    if ($providerMessageBody !== '') {
        $actualMessage = $providerMessageBody;
    }

    if ($queueStatus === 'failed') {
        $failureReason = 'Semaphore status: ' . $providerStatus;
    }

    emarioh_log_sms_queue($db, array_merge($logEntry, [
        'message_body' => $actualMessage,
        'provider_message_id' => $providerMessageId === '' ? null : $providerMessageId,
        'status' => $queueStatus,
        'failure_reason' => $failureReason,
    ]));

    if ($queueStatus === 'failed') {
        return [
            'ok' => false,
            'message' => 'SMS could not be sent right now. Please try again in a moment.',
        ];
    }

    return [
        'ok' => true,
        'provider_status' => $providerStatus,
    ];
}

function emarioh_send_otp_sms(PDO $db, string $recipientName, string $mobile, string $otp, string $purpose): array
{
    $smsConfig = emarioh_config()['sms'] ?? [];
    $semaphoreConfig = is_array($smsConfig['semaphore'] ?? null) ? $smsConfig['semaphore'] : [];
    $apiKey = trim((string) ($semaphoreConfig['api_key'] ?? ''));
    $endpoint = trim((string) ($semaphoreConfig['endpoint'] ?? 'https://api.semaphore.co/api/v4/otp'));
    $senderName = trim((string) ($semaphoreConfig['sender_name'] ?? ''));
    $timeoutSeconds = max(5, (int) ($semaphoreConfig['timeout_seconds'] ?? 15));
    $messageTemplate = emarioh_otp_message_template($purpose);
    $payload = [
        'apikey' => $apiKey,
        'number' => $mobile,
        'message' => $messageTemplate,
        'code' => $otp,
    ];

    if ($senderName !== '') {
        $payload['sendername'] = $senderName;
    }

    $actualMessage = str_replace('{otp}', $otp, $messageTemplate);
    $httpResult = emarioh_http_post_form($endpoint, $payload, $timeoutSeconds);

    if ($httpResult['error'] !== null) {
        emarioh_log_sms_queue($db, [
            'recipient_name' => $recipientName,
            'recipient_mobile' => $mobile,
            'trigger_label' => emarioh_otp_trigger_label($purpose),
            'message_body' => $actualMessage,
            'source_label' => 'OTP Verification',
            'provider_name' => 'Semaphore',
            'provider_message_id' => null,
            'status' => 'failed',
            'failure_reason' => $httpResult['error'],
        ]);

        return [
            'ok' => false,
            'message' => 'OTP could not be sent right now. Please try again in a moment.',
        ];
    }

    if ($httpResult['status_code'] >= 400) {
        $failureReason = 'Semaphore returned HTTP ' . $httpResult['status_code'];
        $responseSummary = emarioh_summarize_http_body($httpResult['body']);

        if ($responseSummary !== '') {
            $failureReason .= ': ' . $responseSummary;
        }

        emarioh_log_sms_queue($db, [
            'recipient_name' => $recipientName,
            'recipient_mobile' => $mobile,
            'trigger_label' => emarioh_otp_trigger_label($purpose),
            'message_body' => $actualMessage,
            'source_label' => 'OTP Verification',
            'provider_name' => 'Semaphore',
            'provider_message_id' => null,
            'status' => 'failed',
            'failure_reason' => $failureReason,
        ]);

        return [
            'ok' => false,
            'message' => 'OTP could not be sent right now. Please try again in a moment.',
        ];
    }

    $decoded = json_decode($httpResult['body'], true);
    $providerMessage = is_array($decoded[0] ?? null) ? $decoded[0] : null;

    if (!is_array($providerMessage)) {
        $failureReason = 'Unexpected Semaphore response.';
        $responseSummary = emarioh_summarize_http_body($httpResult['body']);

        if ($responseSummary !== '') {
            $failureReason .= ' ' . $responseSummary;
        }

        emarioh_log_sms_queue($db, [
            'recipient_name' => $recipientName,
            'recipient_mobile' => $mobile,
            'trigger_label' => emarioh_otp_trigger_label($purpose),
            'message_body' => $actualMessage,
            'source_label' => 'OTP Verification',
            'provider_name' => 'Semaphore',
            'provider_message_id' => null,
            'status' => 'failed',
            'failure_reason' => $failureReason,
        ]);

        return [
            'ok' => false,
            'message' => 'OTP could not be sent right now. Please try again in a moment.',
        ];
    }

    $providerStatus = (string) ($providerMessage['status'] ?? 'Queued');
    $queueStatus = emarioh_map_sms_queue_status($providerStatus);
    $providerMessageBody = trim((string) ($providerMessage['message'] ?? ''));
    $providerMessageId = (string) ($providerMessage['message_id'] ?? '');
    $failureReason = null;

    if ($providerMessageBody !== '') {
        $actualMessage = $providerMessageBody;
    }

    if ($queueStatus === 'failed') {
        $failureReason = 'Semaphore status: ' . $providerStatus;
    }

    emarioh_log_sms_queue($db, [
        'recipient_name' => $recipientName,
        'recipient_mobile' => $mobile,
        'trigger_label' => emarioh_otp_trigger_label($purpose),
        'message_body' => $actualMessage,
        'source_label' => 'OTP Verification',
        'provider_name' => 'Semaphore',
        'provider_message_id' => $providerMessageId === '' ? null : $providerMessageId,
        'status' => $queueStatus,
        'failure_reason' => $failureReason,
    ]);

    if ($queueStatus === 'failed') {
        return [
            'ok' => false,
            'message' => 'OTP could not be sent right now. Please try again in a moment.',
        ];
    }

    return [
        'ok' => true,
        'provider_status' => $providerStatus,
    ];
}

function emarioh_otp_trigger_label(string $purpose): string
{
    return match ($purpose) {
        'admin_setup' => 'Admin setup OTP',
        'admin_mobile_update' => 'Admin mobile update OTP',
        'password_reset' => 'Password reset OTP',
        default => 'Client registration OTP',
    };
}

function emarioh_otp_message_template(string $purpose): string
{
    $validMinutes = max(1, (int) ceil(EMARIOH_OTP_TTL / 60));

    if ($purpose === 'admin_setup') {
        return 'Emarioh Catering admin setup OTP: {otp}. Valid for ' . $validMinutes . ' minutes.';
    }

    if ($purpose === 'admin_mobile_update') {
        return 'Emarioh Catering admin mobile update OTP: {otp}. Verify your new mobile number within ' . $validMinutes . ' minutes.';
    }

    if ($purpose === 'password_reset') {
        return 'Emarioh Catering password reset OTP: {otp}. Use it to create your new password within ' . $validMinutes . ' minutes.';
    }

    return 'Emarioh Catering OTP: {otp}. Use it to finish your account registration. Valid for ' . $validMinutes . ' minutes.';
}

function emarioh_booking_sms_date_label(array $booking): string
{
    $eventDateValue = trim((string) ($booking['event_date'] ?? ''));
    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $eventDateValue);

    if ($eventDate instanceof DateTimeImmutable && $eventDate->format('Y-m-d') === $eventDateValue) {
        return $eventDate->format('F j, Y');
    }

    return 'the scheduled date';
}

function emarioh_booking_sms_time_label(array $booking): string
{
    $eventTimeValue = trim((string) ($booking['event_time'] ?? ''));
    $eventTime = DateTimeImmutable::createFromFormat('H:i:s', $eventTimeValue)
        ?: DateTimeImmutable::createFromFormat('H:i', $eventTimeValue);

    if ($eventTime instanceof DateTimeImmutable) {
        return $eventTime->format('g:i A');
    }

    return 'the scheduled time';
}

function emarioh_booking_sms_schedule_label(array $booking): string
{
    $eventDateValue = trim((string) ($booking['event_date'] ?? ''));
    $eventTimeValue = trim((string) ($booking['event_time'] ?? ''));
    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $eventDateValue);
    $eventTime = DateTimeImmutable::createFromFormat('H:i:s', $eventTimeValue)
        ?: DateTimeImmutable::createFromFormat('H:i', $eventTimeValue);

    if ($eventDate && $eventTime) {
        return $eventDate->format('F j, Y') . ' at ' . $eventTime->format('g:i A');
    }

    if ($eventDate) {
        return $eventDate->format('F j, Y');
    }

    if ($eventTime) {
        return $eventTime->format('g:i A');
    }

    return 'the scheduled date';
}

function emarioh_booking_sms_placeholder_values(array $booking): array
{
    return [
        '[Client Name]' => emarioh_first_name((string) ($booking['primary_contact'] ?? 'Client')),
        '[Booking Ref]' => trim((string) ($booking['reference'] ?? 'your booking')) ?: 'your booking',
        '[Event Date]' => emarioh_booking_sms_date_label($booking),
        '[Event Time]' => emarioh_booking_sms_time_label($booking),
        '[Event Type]' => trim((string) ($booking['event_type'] ?? 'event')) ?: 'event',
        '[Event Schedule]' => emarioh_booking_sms_schedule_label($booking),
    ];
}

function emarioh_send_booking_sms_template(
    PDO $db,
    array $booking,
    string $templateKey,
    array $options = []
): array {
    if (!emarioh_booking_sms_notifications_are_enabled()) {
        return [
            'ok' => false,
            'message' => 'Booking SMS notifications are disabled.',
            'skipped' => true,
        ];
    }

    $template = emarioh_find_sms_template_by_key($db, $templateKey);

    if ($template === null) {
        return [
            'ok' => false,
            'message' => 'SMS template could not be found.',
            'skipped' => true,
        ];
    }

    if (array_key_exists('is_active', $template) && !(bool) ($template['is_active'] ?? false)) {
        return [
            'ok' => false,
            'message' => 'SMS template is inactive.',
            'skipped' => true,
        ];
    }

    $messageBody = emarioh_render_sms_template_body(
        (string) ($template['template_body'] ?? ''),
        array_merge(
            emarioh_booking_sms_placeholder_values($booking),
            is_array($options['placeholders'] ?? null) ? $options['placeholders'] : []
        )
    );

    if ($messageBody === '') {
        return [
            'ok' => false,
            'message' => 'SMS template body is empty.',
            'skipped' => true,
        ];
    }

    return emarioh_send_sms_message(
        $db,
        (string) ($booking['primary_contact'] ?? 'Client'),
        emarioh_normalize_mobile((string) ($booking['primary_mobile'] ?? '')),
        $messageBody,
        [
            'template_id' => (int) ($template['id'] ?? 0),
            'booking_id' => (int) ($booking['id'] ?? 0),
            'scheduled_at' => $options['scheduled_at'] ?? null,
            'trigger_label' => (string) ($options['trigger_label'] ?? ($template['trigger_label'] ?? 'Booking update')),
            'source_label' => (string) ($options['source_label'] ?? 'Booking Management'),
        ]
    );
}

function emarioh_send_booking_status_sms(PDO $db, array $booking, string $status): array
{
    $templateKey = match ($status) {
        'approved' => 'booking_approved',
        'rejected' => 'booking_rejected',
        default => '',
    };

    if ($templateKey === '') {
        return [
            'ok' => false,
            'message' => 'Booking status SMS is only available for approved or rejected bookings.',
            'skipped' => true,
        ];
    }

    return emarioh_send_booking_sms_template(
        $db,
        $booking,
        $templateKey,
        [
            'trigger_label' => $status === 'approved' ? 'Booking approved' : 'Booking rejected',
            'source_label' => 'Booking Management',
        ]
    );
}

function emarioh_can_send_downpayment_reminder(array $booking, ?array $invoice): bool
{
    if ($invoice === null) {
        return false;
    }

    $bookingStatus = strtolower(trim((string) ($booking['status'] ?? 'pending_review')));
    $invoiceStatus = strtolower(trim((string) ($invoice['status'] ?? 'pending')));
    $amountPaidValue = max(0, (float) ($invoice['amount_paid'] ?? 0));
    $balanceDueValue = max(0, (float) ($invoice['balance_due'] ?? 0));

    return in_array($bookingStatus, ['approved', 'completed'], true)
        && in_array($invoiceStatus, ['pending', 'review'], true)
        && $amountPaidValue <= 0.00001
        && $balanceDueValue > 0.00001;
}

function emarioh_can_send_final_event_reminder(array $booking): bool
{
    $bookingStatus = strtolower(trim((string) ($booking['status'] ?? 'pending_review')));

    if (!in_array($bookingStatus, ['approved', 'completed'], true)) {
        return false;
    }

    $eventDateValue = trim((string) ($booking['event_date'] ?? ''));
    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $eventDateValue);

    if (!$eventDate instanceof DateTimeImmutable || $eventDate->format('Y-m-d') !== $eventDateValue) {
        return false;
    }

    return $eventDate >= new DateTimeImmutable('today');
}

function emarioh_map_sms_queue_status(string $providerStatus): string
{
    return match (strtolower(trim($providerStatus))) {
        'sent' => 'sent',
        'failed', 'refunded', 'rejected' => 'failed',
        default => 'queued',
    };
}

function emarioh_release_curl_handle($curl): void
{
    if ($curl === null || $curl === false || PHP_VERSION_ID >= 80000) {
        return;
    }

    if (!function_exists('curl_close')) {
        return;
    }

    call_user_func('curl_close', $curl);
}

function emarioh_http_post_form(string $url, array $payload, int $timeoutSeconds): array
{
    $body = http_build_query($payload);

    if (function_exists('curl_init')) {
        $curl = curl_init($url);

        if ($curl === false) {
            return [
                'status_code' => 0,
                'body' => '',
                'error' => 'Failed to initialize cURL.',
            ];
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $responseBody = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        emarioh_release_curl_handle($curl);

        return [
            'status_code' => $statusCode,
            'body' => is_string($responseBody) ? $responseBody : '',
            'error' => $error !== '' ? $error : null,
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body,
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
        ],
    ]);
    $responseBody = @file_get_contents($url, false, $context);
    $statusCode = 0;
    $responseHeaders = $http_response_header ?? [];

    foreach ($responseHeaders as $headerLine) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', $headerLine, $matches)) {
            $statusCode = (int) $matches[1];
            break;
        }
    }

    return [
        'status_code' => $statusCode,
        'body' => is_string($responseBody) ? $responseBody : '',
        'error' => $responseBody === false ? 'Failed to connect to Semaphore.' : null,
    ];
}

function emarioh_http_request_json(
    string $method,
    string $url,
    array $headers = [],
    ?array $payload = null,
    int $timeoutSeconds = 20
): array {
    $normalizedMethod = strtoupper(trim($method));
    $requestHeaders = array_values(array_filter(array_merge([
        'Accept: application/json',
    ], $headers)));
    $body = $payload === null
        ? null
        : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($payload !== null) {
        $hasContentType = false;

        foreach ($requestHeaders as $headerValue) {
            if (stripos($headerValue, 'Content-Type:') === 0) {
                $hasContentType = true;
                break;
            }
        }

        if (!$hasContentType) {
            $requestHeaders[] = 'Content-Type: application/json';
        }
    }

    if (function_exists('curl_init')) {
        $curl = curl_init($url);

        if ($curl === false) {
            return [
                'status_code' => 0,
                'body' => '',
                'error' => 'Failed to initialize cURL.',
            ];
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $normalizedMethod,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if ($body !== null && $normalizedMethod !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        emarioh_release_curl_handle($curl);

        return [
            'status_code' => $statusCode,
            'body' => is_string($responseBody) ? $responseBody : '',
            'error' => $error !== '' ? $error : null,
        ];
    }

    $headerString = implode("\r\n", $requestHeaders);
    if ($headerString !== '') {
        $headerString .= "\r\n";
    }

    $context = stream_context_create([
        'http' => [
            'method' => $normalizedMethod,
            'header' => $headerString,
            'content' => $body ?? '',
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
        ],
    ]);
    $responseBody = @file_get_contents($url, false, $context);
    $statusCode = 0;
    $responseHeaders = $http_response_header ?? [];

    foreach ($responseHeaders as $headerLine) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', $headerLine, $matches)) {
            $statusCode = (int) $matches[1];
            break;
        }
    }

    return [
        'status_code' => $statusCode,
        'body' => is_string($responseBody) ? $responseBody : '',
        'error' => $responseBody === false ? 'Failed to connect to remote service.' : null,
    ];
}

function emarioh_summarize_http_body(string $body): string
{
    $summary = trim(preg_replace('/\s+/', ' ', $body) ?? '');

    if ($summary === '') {
        return '';
    }

    return function_exists('mb_substr')
        ? mb_substr($summary, 0, 180)
        : substr($summary, 0, 180);
}

function emarioh_log_sms_queue(PDO $db, array $entry): void
{
    try {
        $status = in_array((string) ($entry['status'] ?? 'queued'), ['queued', 'sent', 'failed', 'cancelled'], true)
            ? (string) $entry['status']
            : 'queued';
        $templateId = (int) ($entry['template_id'] ?? 0);
        $bookingId = (int) ($entry['booking_id'] ?? 0);
        $inquiryId = (int) ($entry['inquiry_id'] ?? 0);

        $db->prepare('
            INSERT INTO sms_queue (
                template_id,
                booking_id,
                inquiry_id,
                recipient_name,
                recipient_mobile,
                trigger_label,
                message_body,
                source_label,
                provider_name,
                provider_message_id,
                scheduled_at,
                sent_at,
                failed_at,
                status,
                failure_reason
            ) VALUES (
                :template_id,
                :booking_id,
                :inquiry_id,
                :recipient_name,
                :recipient_mobile,
                :trigger_label,
                :message_body,
                :source_label,
                :provider_name,
                :provider_message_id,
                :scheduled_at,
                :sent_at,
                :failed_at,
                :status,
                :failure_reason
            )
        ')->execute([
            ':template_id' => $templateId > 0 ? $templateId : null,
            ':booking_id' => $bookingId > 0 ? $bookingId : null,
            ':inquiry_id' => $inquiryId > 0 ? $inquiryId : null,
            ':recipient_name' => (string) ($entry['recipient_name'] ?? 'Client'),
            ':recipient_mobile' => (string) ($entry['recipient_mobile'] ?? ''),
            ':trigger_label' => (string) ($entry['trigger_label'] ?? 'SMS Notification'),
            ':message_body' => (string) ($entry['message_body'] ?? ''),
            ':source_label' => (string) ($entry['source_label'] ?? 'System'),
            ':provider_name' => (string) ($entry['provider_name'] ?? 'Semaphore'),
            ':provider_message_id' => $entry['provider_message_id'] ?? null,
            ':scheduled_at' => $entry['scheduled_at'] ?? null,
            ':sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
            ':failed_at' => $status === 'failed' ? date('Y-m-d H:i:s') : null,
            ':status' => $status,
            ':failure_reason' => $entry['failure_reason'] ?? null,
        ]);
    } catch (Throwable $exception) {
        // Queue logging should not block OTP delivery.
    }
}

function emarioh_set_remember_cookie(string $selector, string $validator, int $expiresAt): void
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie(EMARIOH_REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires' => $expiresAt,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function emarioh_clear_remember_cookie(): void
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie(EMARIOH_REMEMBER_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function emarioh_parse_remember_cookie(): ?array
{
    $cookieValue = trim((string) ($_COOKIE[EMARIOH_REMEMBER_COOKIE] ?? ''));

    if ($cookieValue === '' || !str_contains($cookieValue, ':')) {
        return null;
    }

    [$selector, $validator] = explode(':', $cookieValue, 2);

    if (!preg_match('/^[a-f0-9]+$/i', $selector) || !preg_match('/^[a-f0-9]+$/i', $validator)) {
        return null;
    }

    return [
        'selector' => $selector,
        'validator' => $validator,
    ];
}

function emarioh_issue_remember_token(PDO $db, int $userId): void
{
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(33));
    $expiresAt = time() + EMARIOH_REMEMBER_TTL;

    $db->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id')
        ->execute([
            ':user_id' => $userId,
        ]);

    $db->prepare('
        INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at, created_at)
        VALUES (:user_id, :selector, :token_hash, :expires_at, :created_at)
    ')->execute([
        ':user_id' => $userId,
        ':selector' => $selector,
        ':token_hash' => hash('sha256', $validator),
        ':expires_at' => $expiresAt,
        ':created_at' => time(),
    ]);

    emarioh_set_remember_cookie($selector, $validator, $expiresAt);
}

function emarioh_remove_remember_token(PDO $db): void
{
    $rememberCookie = emarioh_parse_remember_cookie();

    if ($rememberCookie !== null) {
        $db->prepare('DELETE FROM remember_tokens WHERE selector = :selector')
            ->execute([
                ':selector' => $rememberCookie['selector'],
            ]);
    }

    emarioh_clear_remember_cookie();
}

function emarioh_revoke_all_remember_tokens(PDO $db, int $userId): void
{
    $db->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id')
        ->execute([
            ':user_id' => $userId,
        ]);
}

function emarioh_restore_user_from_remember_cookie(PDO $db): ?array
{
    $rememberCookie = emarioh_parse_remember_cookie();

    if ($rememberCookie === null) {
        return null;
    }

    $statement = $db->prepare('
        SELECT *
        FROM remember_tokens
        WHERE selector = :selector AND expires_at >= :now
        LIMIT 1
    ');
    $statement->execute([
        ':selector' => $rememberCookie['selector'],
        ':now' => time(),
    ]);

    $token = $statement->fetch();

    if (!is_array($token)) {
        emarioh_clear_remember_cookie();
        return null;
    }

    $validatorHash = hash('sha256', $rememberCookie['validator']);

    if (!hash_equals((string) $token['token_hash'], $validatorHash)) {
        $db->prepare('DELETE FROM remember_tokens WHERE selector = :selector')
            ->execute([
                ':selector' => $rememberCookie['selector'],
            ]);
        emarioh_clear_remember_cookie();
        return null;
    }

    $user = emarioh_find_user_by_id($db, (int) $token['user_id']);

    if ($user === null) {
        $db->prepare('DELETE FROM remember_tokens WHERE selector = :selector')
            ->execute([
                ':selector' => $rememberCookie['selector'],
            ]);
        emarioh_clear_remember_cookie();
        return null;
    }

    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'user_id' => (int) $user['id'],
    ];

    $db->prepare('UPDATE remember_tokens SET last_used_at = :last_used_at WHERE id = :id')
        ->execute([
            ':last_used_at' => time(),
            ':id' => (int) $token['id'],
        ]);

    return $user;
}

function emarioh_current_user(): ?array
{
    static $hasLoaded = false;
    static $currentUser = null;

    if ($hasLoaded) {
        return $currentUser;
    }

    $hasLoaded = true;
    $db = emarioh_db();
    $userId = (int) ($_SESSION['auth']['user_id'] ?? 0);

    if ($userId > 0) {
        $currentUser = emarioh_find_user_by_id($db, $userId);

        if ($currentUser !== null) {
            return $currentUser;
        }

        unset($_SESSION['auth']);
    }

    $currentUser = emarioh_restore_user_from_remember_cookie($db);
    return $currentUser;
}

function emarioh_login_user(PDO $db, array $user, bool $remember = false): void
{
    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'user_id' => (int) $user['id'],
    ];

    $db->prepare('UPDATE users SET last_login_at = :last_login_at, updated_at = :updated_at WHERE id = :id')
        ->execute([
            ':last_login_at' => time(),
            ':updated_at' => time(),
            ':id' => (int) $user['id'],
        ]);

    if ($remember) {
        emarioh_issue_remember_token($db, (int) $user['id']);
        return;
    }

    emarioh_remove_remember_token($db);
}

function emarioh_logout_user(PDO $db): void
{
    emarioh_remove_remember_token($db);
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $isHttps = emarioh_request_is_https();

        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}

function emarioh_redirect(string $url): never
{
    header('Location: ' . emarioh_resolve_redirect_url($url));
    exit;
}

function emarioh_require_authenticated_user(): array
{
    $currentUser = emarioh_current_user();

    if ($currentUser === null) {
        emarioh_fail('Please sign in to continue.', 401, [
            'redirect_url' => 'login.php?reason=session',
        ]);
    }

    return $currentUser;
}

function emarioh_require_role(string $role): array
{
    $currentUser = emarioh_require_authenticated_user();

    if ((string) ($currentUser['role'] ?? '') !== $role) {
        emarioh_fail('You do not have access to this action.', 403, [
            'redirect_url' => emarioh_role_landing_url((string) ($currentUser['role'] ?? 'client')),
        ]);
    }

    return $currentUser;
}

function emarioh_require_page_role(string $role): array
{
    $currentUser = emarioh_current_user();

    if ($currentUser === null) {
        if ($role === 'admin') {
            emarioh_redirect('login.php?reason=admin_only');
        }

        emarioh_redirect('login.php');
    }

    if ((string) ($currentUser['role'] ?? '') !== $role) {
        emarioh_redirect(emarioh_role_landing_url((string) ($currentUser['role'] ?? 'client')));
    }

    return $currentUser;
}

function emarioh_trim_or_null(?string $value): ?string
{
    $normalized = trim((string) $value);
    return $normalized === '' ? null : $normalized;
}

function emarioh_default_public_site_settings(): array
{
    return [
        'id' => 1,
        'hero_image_path' => null,
        'hero_image_alt' => 'Emarioh Catering Services hero image',
        'primary_mobile' => null,
        'secondary_mobile' => null,
        'public_email' => null,
        'inquiry_email' => null,
        'facebook_url' => null,
        'messenger_url' => null,
        'service_area' => null,
        'business_hours' => null,
        'business_address' => null,
        'map_embed_url' => null,
    ];
}

function emarioh_default_public_service_cards(): array
{
    return [
        'service_1' => [
            'slot_key' => 'service_1',
            'title' => 'Weddings & Birthdays',
            'description' => 'Exquisite setups, curated menus, and polished catering service for weddings, birthdays, and meaningful family celebrations.',
            'image_path' => null,
            'image_alt' => 'Weddings & Birthdays service image',
            'sort_order' => 1,
            'is_active' => 1,
        ],
        'service_2' => [
            'slot_key' => 'service_2',
            'title' => 'Corporate Catering',
            'description' => 'Professional catering support for meetings, seminars, trainings, company gatherings, and formal corporate events.',
            'image_path' => null,
            'image_alt' => 'Corporate Catering service image',
            'sort_order' => 2,
            'is_active' => 1,
        ],
        'service_3' => [
            'slot_key' => 'service_3',
            'title' => 'Debut & Social Events',
            'description' => 'Memorable styling, buffet presentation, and guest-ready service for debuts, anniversaries, reunions, and special occasions.',
            'image_path' => null,
            'image_alt' => 'Debut & Social Events service image',
            'sort_order' => 3,
            'is_active' => 1,
        ],
    ];
}

function emarioh_normalize_public_asset_path(?string $value, string $fallback = ''): string
{
    $normalized = str_replace('\\', '/', trim((string) $value));

    if ($normalized === '' || str_contains($normalized, '..')) {
        return $fallback;
    }

    if (preg_match('#^(?:assets|uploads)/[A-Za-z0-9._/-]+$#', $normalized) !== 1) {
        return $fallback;
    }

    return $normalized;
}

function emarioh_public_upload_buckets(): array
{
    return [
        'hero',
        'services',
        'gallery',
    ];
}

function emarioh_normalize_public_upload_bucket(string $bucket): string
{
    $normalizedBucket = strtolower(trim($bucket));

    if (!in_array($normalizedBucket, emarioh_public_upload_buckets(), true)) {
        throw new InvalidArgumentException('Unsupported upload bucket.');
    }

    return $normalizedBucket;
}

function emarioh_public_uploads_storage_path(): string
{
    return emarioh_storage_path() . DIRECTORY_SEPARATOR . 'uploads';
}

function emarioh_public_upload_relative_directory(string $bucket): string
{
    return 'uploads/' . emarioh_normalize_public_upload_bucket($bucket);
}

function emarioh_public_upload_absolute_directory(string $bucket): string
{
    return emarioh_public_uploads_storage_path() . DIRECTORY_SEPARATOR . emarioh_normalize_public_upload_bucket($bucket);
}

function emarioh_ensure_public_upload_directory(string $bucket): string
{
    $absoluteDirectory = emarioh_public_upload_absolute_directory($bucket);
    $bucketLabel = emarioh_normalize_public_upload_bucket($bucket);

    emarioh_ensure_directory_exists(
        $absoluteDirectory,
        sprintf('The %s upload directory could not be created. Check storage.path and write permissions.', $bucketLabel)
    );

    return $absoluteDirectory;
}

function emarioh_public_asset_is_storage_upload(string $relativePath): bool
{
    $normalized = emarioh_normalize_public_asset_path($relativePath);

    return $normalized !== '' && str_starts_with($normalized, 'uploads/');
}

function emarioh_public_asset_matches_upload_bucket(string $relativePath, string $bucket): bool
{
    $normalized = emarioh_normalize_public_asset_path($relativePath);

    if ($normalized === '') {
        return false;
    }

    $normalizedBucket = emarioh_normalize_public_upload_bucket($bucket);

    return str_starts_with($normalized, 'uploads/' . $normalizedBucket . '/')
        || str_starts_with($normalized, 'assets/images/uploads/' . $normalizedBucket . '/');
}

function emarioh_public_asset_absolute_path(string $relativePath): ?string
{
    $normalized = emarioh_normalize_public_asset_path($relativePath);

    if ($normalized === '') {
        return null;
    }

    if (emarioh_public_asset_is_storage_upload($normalized)) {
        return emarioh_public_uploads_storage_path() . DIRECTORY_SEPARATOR . ltrim(substr($normalized, strlen('uploads/')), '/');
    }

    return EMARIOH_BASE_PATH . '/' . $normalized;
}

function emarioh_public_asset_url(string $relativePath): string
{
    $normalized = emarioh_normalize_public_asset_path($relativePath);

    if ($normalized === '') {
        return '';
    }

    if (emarioh_public_asset_is_storage_upload($normalized)) {
        return emarioh_app_url('media.php?asset=' . rawurlencode($normalized));
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseDirectory = str_replace('\\', '/', dirname($scriptName));

    if ($baseDirectory === '.' || $baseDirectory === '/' || $baseDirectory === '\\') {
        return '/' . ltrim($normalized, '/');
    }

    return rtrim($baseDirectory, '/') . '/' . ltrim($normalized, '/');
}

function emarioh_fetch_public_site_settings(PDO $db): array
{
    $defaults = emarioh_default_public_site_settings();
    $statement = $db->query('SELECT * FROM public_site_settings WHERE id = 1 LIMIT 1');
    $settings = $statement->fetch();

    if (!is_array($settings)) {
        return $defaults;
    }

    $settings['hero_image_path'] = emarioh_normalize_public_asset_path(
        (string) ($settings['hero_image_path'] ?? ''),
        (string) $defaults['hero_image_path']
    );
    $settings['hero_image_alt'] = emarioh_trim_or_null((string) ($settings['hero_image_alt'] ?? ''))
        ?? (string) $defaults['hero_image_alt'];
    $settings['primary_mobile'] = emarioh_trim_or_null((string) ($settings['primary_mobile'] ?? ''));
    $settings['secondary_mobile'] = emarioh_trim_or_null((string) ($settings['secondary_mobile'] ?? ''));
    $settings['public_email'] = emarioh_trim_or_null((string) ($settings['public_email'] ?? ''));
    $settings['inquiry_email'] = emarioh_trim_or_null((string) ($settings['inquiry_email'] ?? ''));
    $settings['facebook_url'] = emarioh_trim_or_null((string) ($settings['facebook_url'] ?? ''));
    $settings['messenger_url'] = emarioh_trim_or_null((string) ($settings['messenger_url'] ?? ''));
    $settings['service_area'] = emarioh_trim_or_null((string) ($settings['service_area'] ?? ''));
    $settings['business_hours'] = emarioh_trim_or_null((string) ($settings['business_hours'] ?? ''));
    $settings['business_address'] = emarioh_trim_or_null((string) ($settings['business_address'] ?? ''));
    $settings['map_embed_url'] = emarioh_trim_or_null((string) ($settings['map_embed_url'] ?? ''));

    return array_merge($defaults, $settings);
}

function emarioh_save_public_site_settings(PDO $db, array $changes): array
{
    $defaults = emarioh_default_public_site_settings();
    $current = emarioh_fetch_public_site_settings($db);
    $next = [
        'id' => 1,
        'hero_image_path' => array_key_exists('hero_image_path', $changes)
            ? emarioh_normalize_public_asset_path(
                (string) $changes['hero_image_path'],
                (string) $defaults['hero_image_path']
            )
            : (string) ($current['hero_image_path'] ?? $defaults['hero_image_path']),
        'hero_image_alt' => array_key_exists('hero_image_alt', $changes)
            ? (emarioh_trim_or_null((string) $changes['hero_image_alt']) ?? (string) $defaults['hero_image_alt'])
            : ((string) ($current['hero_image_alt'] ?? $defaults['hero_image_alt']) ?: (string) $defaults['hero_image_alt']),
        'primary_mobile' => array_key_exists('primary_mobile', $changes)
            ? emarioh_trim_or_null((string) $changes['primary_mobile'])
            : emarioh_trim_or_null((string) ($current['primary_mobile'] ?? '')),
        'secondary_mobile' => array_key_exists('secondary_mobile', $changes)
            ? emarioh_trim_or_null((string) $changes['secondary_mobile'])
            : emarioh_trim_or_null((string) ($current['secondary_mobile'] ?? '')),
        'public_email' => array_key_exists('public_email', $changes)
            ? emarioh_trim_or_null((string) $changes['public_email'])
            : emarioh_trim_or_null((string) ($current['public_email'] ?? '')),
        'inquiry_email' => array_key_exists('inquiry_email', $changes)
            ? emarioh_trim_or_null((string) $changes['inquiry_email'])
            : emarioh_trim_or_null((string) ($current['inquiry_email'] ?? '')),
        'facebook_url' => array_key_exists('facebook_url', $changes)
            ? emarioh_trim_or_null((string) $changes['facebook_url'])
            : emarioh_trim_or_null((string) ($current['facebook_url'] ?? '')),
        'messenger_url' => array_key_exists('messenger_url', $changes)
            ? emarioh_trim_or_null((string) $changes['messenger_url'])
            : emarioh_trim_or_null((string) ($current['messenger_url'] ?? '')),
        'service_area' => array_key_exists('service_area', $changes)
            ? emarioh_trim_or_null((string) $changes['service_area'])
            : emarioh_trim_or_null((string) ($current['service_area'] ?? '')),
        'business_hours' => array_key_exists('business_hours', $changes)
            ? emarioh_trim_or_null((string) $changes['business_hours'])
            : emarioh_trim_or_null((string) ($current['business_hours'] ?? '')),
        'business_address' => array_key_exists('business_address', $changes)
            ? emarioh_trim_or_null((string) $changes['business_address'])
            : emarioh_trim_or_null((string) ($current['business_address'] ?? '')),
        'map_embed_url' => array_key_exists('map_embed_url', $changes)
            ? emarioh_trim_or_null((string) $changes['map_embed_url'])
            : emarioh_trim_or_null((string) ($current['map_embed_url'] ?? '')),
    ];

    $db->prepare('
        INSERT INTO public_site_settings (
            id,
            hero_image_path,
            hero_image_alt,
            primary_mobile,
            secondary_mobile,
            public_email,
            inquiry_email,
            facebook_url,
            messenger_url,
            service_area,
            business_hours,
            business_address,
            map_embed_url
        ) VALUES (
            :id,
            :hero_image_path,
            :hero_image_alt,
            :primary_mobile,
            :secondary_mobile,
            :public_email,
            :inquiry_email,
            :facebook_url,
            :messenger_url,
            :service_area,
            :business_hours,
            :business_address,
            :map_embed_url
        )
        ON DUPLICATE KEY UPDATE
            hero_image_path = VALUES(hero_image_path),
            hero_image_alt = VALUES(hero_image_alt),
            primary_mobile = VALUES(primary_mobile),
            secondary_mobile = VALUES(secondary_mobile),
            public_email = VALUES(public_email),
            inquiry_email = VALUES(inquiry_email),
            facebook_url = VALUES(facebook_url),
            messenger_url = VALUES(messenger_url),
            service_area = VALUES(service_area),
            business_hours = VALUES(business_hours),
            business_address = VALUES(business_address),
            map_embed_url = VALUES(map_embed_url)
    ')->execute([
        ':id' => 1,
        ':hero_image_path' => $next['hero_image_path'],
        ':hero_image_alt' => $next['hero_image_alt'],
        ':primary_mobile' => $next['primary_mobile'],
        ':secondary_mobile' => $next['secondary_mobile'],
        ':public_email' => $next['public_email'],
        ':inquiry_email' => $next['inquiry_email'],
        ':facebook_url' => $next['facebook_url'],
        ':messenger_url' => $next['messenger_url'],
        ':service_area' => $next['service_area'],
        ':business_hours' => $next['business_hours'],
        ':business_address' => $next['business_address'],
        ':map_embed_url' => $next['map_embed_url'],
    ]);

    return emarioh_fetch_public_site_settings($db);
}

function emarioh_fetch_public_service_cards(PDO $db): array
{
    $defaults = emarioh_default_public_service_cards();
    $rows = $db->query('
        SELECT
            slot_key,
            title,
            description,
            image_path,
            image_alt,
            sort_order,
            is_active
        FROM public_service_cards
        ORDER BY sort_order ASC, id ASC
    ')->fetchAll();

    foreach ($rows as $row) {
        $slotKey = (string) ($row['slot_key'] ?? '');

        if (!array_key_exists($slotKey, $defaults)) {
            continue;
        }

        $default = $defaults[$slotKey];
        $title = trim((string) ($row['title'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $imagePath = emarioh_normalize_public_asset_path((string) ($row['image_path'] ?? ''), '');
        $imageAlt = emarioh_trim_or_null((string) ($row['image_alt'] ?? ''));

        $defaults[$slotKey] = [
            'slot_key' => $slotKey,
            'title' => $title !== '' ? $title : (string) $default['title'],
            'description' => $description !== '' ? $description : (string) $default['description'],
            'image_path' => $imagePath !== '' ? $imagePath : null,
            'image_alt' => $imageAlt ?? (string) $default['image_alt'],
            'sort_order' => (int) ($default['sort_order'] ?? 0),
            'is_active' => (int) ($row['is_active'] ?? 1) === 1 ? 1 : 0,
        ];
    }

    return $defaults;
}

function emarioh_save_public_service_cards(PDO $db, array $changes): array
{
    $defaults = emarioh_default_public_service_cards();
    $current = emarioh_fetch_public_service_cards($db);
    $statement = $db->prepare('
        INSERT INTO public_service_cards (
            slot_key,
            title,
            description,
            image_path,
            image_alt,
            sort_order,
            is_active
        ) VALUES (
            :slot_key,
            :title,
            :description,
            :image_path,
            :image_alt,
            :sort_order,
            :is_active
        )
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            image_path = VALUES(image_path),
            image_alt = VALUES(image_alt),
            sort_order = VALUES(sort_order),
            is_active = VALUES(is_active)
    ');

    foreach ($defaults as $slotKey => $default) {
        $incoming = $changes[$slotKey] ?? null;
        $incoming = is_array($incoming) ? $incoming : [];
        $base = $current[$slotKey] ?? $default;
        $title = trim((string) ($incoming['title'] ?? ($base['title'] ?? $default['title'])));
        $description = trim((string) ($incoming['description'] ?? ($base['description'] ?? $default['description'])));
        $imagePath = array_key_exists('image_path', $incoming)
            ? emarioh_normalize_public_asset_path((string) $incoming['image_path'], '')
            : emarioh_normalize_public_asset_path((string) ($base['image_path'] ?? ''), '');
        $imageAlt = array_key_exists('image_alt', $incoming)
            ? (emarioh_trim_or_null((string) $incoming['image_alt']) ?? '')
            : (emarioh_trim_or_null((string) ($base['image_alt'] ?? '')) ?? '');

        if ($title === '') {
            $title = (string) $default['title'];
        }

        if ($description === '') {
            $description = (string) $default['description'];
        }

        if ($imageAlt === '') {
            $imageAlt = $title . ' service image';
        }

        $statement->execute([
            ':slot_key' => $slotKey,
            ':title' => $title,
            ':description' => $description,
            ':image_path' => $imagePath !== '' ? $imagePath : null,
            ':image_alt' => $imageAlt,
            ':sort_order' => (int) ($default['sort_order'] ?? 0),
            ':is_active' => 1,
        ]);
    }

    return emarioh_fetch_public_service_cards($db);
}

function emarioh_gallery_category_options(): array
{
    return [
        'wedding' => 'Wedding',
        'birthday' => 'Birthday',
        'corporate' => 'Corporate',
        'social' => 'Social',
    ];
}

function emarioh_normalize_gallery_category(?string $value, string $fallback = 'wedding'): string
{
    $categories = emarioh_gallery_category_options();
    $normalized = strtolower(trim((string) $value));

    return array_key_exists($normalized, $categories) ? $normalized : $fallback;
}

function emarioh_map_gallery_item_row(array $row): array
{
    $categories = emarioh_gallery_category_options();
    $category = emarioh_normalize_gallery_category((string) ($row['category'] ?? ''), 'wedding');
    $title = trim((string) ($row['title'] ?? ''));
    $fileName = emarioh_trim_or_null((string) ($row['file_name'] ?? ''));
    $imagePath = emarioh_normalize_public_asset_path((string) ($row['image_path'] ?? ''), '');
    $imageAlt = emarioh_trim_or_null((string) ($row['image_alt'] ?? ''));
    $placementLabel = emarioh_trim_or_null((string) ($row['placement_label'] ?? ''))
        ?? (string) ($categories[$category] ?? 'Gallery');

    if ($title === '') {
        $title = 'Gallery image';
    }

    if ($fileName === null || $fileName === '') {
        $fileName = $imagePath !== '' ? basename($imagePath) : 'Uploaded image';
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'title' => $title,
        'category' => $category,
        'category_label' => (string) ($categories[$category] ?? 'Gallery'),
        'file_name' => $fileName,
        'image_path' => $imagePath !== '' ? $imagePath : null,
        'image_alt' => $imageAlt ?? ($title . ' gallery image'),
        'placement_label' => $placementLabel,
        'sort_order' => (int) ($row['sort_order'] ?? 0),
        'status' => strtolower((string) ($row['status'] ?? 'active')) === 'archived' ? 'archived' : 'active',
    ];
}

function emarioh_fetch_gallery_items(PDO $db): array
{
    $rows = $db->query('
        SELECT
            id,
            title,
            category,
            file_name,
            image_path,
            image_alt,
            placement_label,
            sort_order,
            status
        FROM gallery_items
        WHERE status = \'active\'
        ORDER BY sort_order ASC, id ASC
    ')->fetchAll();

    return array_map(
        static fn (array $row): array => emarioh_map_gallery_item_row($row),
        array_filter($rows, 'is_array')
    );
}

function emarioh_find_gallery_item(PDO $db, int $galleryItemId): ?array
{
    $statement = $db->prepare('
        SELECT
            id,
            title,
            category,
            file_name,
            image_path,
            image_alt,
            placement_label,
            sort_order,
            status
        FROM gallery_items
        WHERE id = :id
        LIMIT 1
    ');
    $statement->execute([
        ':id' => $galleryItemId,
    ]);

    $row = $statement->fetch();

    return is_array($row) ? emarioh_map_gallery_item_row($row) : null;
}

function emarioh_save_gallery_item(PDO $db, array $changes): array
{
    $category = emarioh_normalize_gallery_category((string) ($changes['category'] ?? 'wedding'), 'wedding');
    $categoryOptions = emarioh_gallery_category_options();
    $title = trim((string) ($changes['title'] ?? ''));
    $fileName = emarioh_trim_or_null((string) ($changes['file_name'] ?? ''));
    $imagePath = emarioh_normalize_public_asset_path((string) ($changes['image_path'] ?? ''), '');
    $imageAlt = emarioh_trim_or_null((string) ($changes['image_alt'] ?? ''));
    $placementLabel = emarioh_trim_or_null((string) ($changes['placement_label'] ?? ''))
        ?? (string) ($categoryOptions[$category] ?? 'Gallery');
    $status = strtolower((string) ($changes['status'] ?? 'active')) === 'archived' ? 'archived' : 'active';

    if ($title === '') {
        $title = 'Gallery image';
    }

    if ($imageAlt === null || $imageAlt === '') {
        $imageAlt = $title . ' gallery image';
    }

    if ($fileName === null || $fileName === '') {
        $fileName = $imagePath !== '' ? basename($imagePath) : null;
    }

    $sortOrderValue = $changes['sort_order'] ?? null;
    $sortOrder = is_numeric($sortOrderValue)
        ? max(1, (int) $sortOrderValue)
        : max(1, (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_items')->fetchColumn());

    $statement = $db->prepare('
        INSERT INTO gallery_items (
            title,
            category,
            file_name,
            image_path,
            image_alt,
            placement_label,
            sort_order,
            status
        ) VALUES (
            :title,
            :category,
            :file_name,
            :image_path,
            :image_alt,
            :placement_label,
            :sort_order,
            :status
        )
    ');
    $statement->execute([
        ':title' => $title,
        ':category' => $category,
        ':file_name' => $fileName,
        ':image_path' => $imagePath !== '' ? $imagePath : null,
        ':image_alt' => $imageAlt,
        ':placement_label' => $placementLabel,
        ':sort_order' => $sortOrder,
        ':status' => $status,
    ]);

    return emarioh_find_gallery_item($db, (int) $db->lastInsertId()) ?? [
        'id' => 0,
        'title' => $title,
        'category' => $category,
        'category_label' => (string) ($categoryOptions[$category] ?? 'Gallery'),
        'file_name' => $fileName ?? 'Uploaded image',
        'image_path' => $imagePath !== '' ? $imagePath : null,
        'image_alt' => $imageAlt,
        'placement_label' => $placementLabel,
        'sort_order' => $sortOrder,
        'status' => $status,
    ];
}

function emarioh_delete_gallery_item(PDO $db, int $galleryItemId): ?array
{
    $galleryItem = emarioh_find_gallery_item($db, $galleryItemId);

    if ($galleryItem === null) {
        return null;
    }

    $statement = $db->prepare('DELETE FROM gallery_items WHERE id = :id LIMIT 1');
    $statement->execute([
        ':id' => $galleryItemId,
    ]);

    return $galleryItem;
}

function emarioh_infer_inquiry_category(string $messageText): string
{
    $normalizedText = strtolower(trim($messageText));

    if ($normalizedText === '') {
        return 'General Inquiry';
    }

    if (preg_match('/(package|menu|buffet|pax|dessert|food)/', $normalizedText) === 1) {
        return 'Package Inquiry';
    }

    if (preg_match('/(book|booking|reserve|reservation|date|available)/', $normalizedText) === 1) {
        return 'Booking Inquiry';
    }

    return 'Event Inquiry';
}

function emarioh_generate_website_inquiry_reference(PDO $db): string
{
    $prefix = 'INQ-' . date('Ymd') . '-';
    $statement = $db->prepare('
        SELECT reference
        FROM website_inquiries
        WHERE reference LIKE :reference_prefix
        ORDER BY reference DESC
        LIMIT 1
    ');
    $statement->execute([
        ':reference_prefix' => $prefix . '%',
    ]);

    $latestReference = (string) ($statement->fetchColumn() ?: '');
    $nextSequence = 1;

    if ($latestReference !== '' && preg_match('/-(\d{3})$/', $latestReference, $matches) === 1) {
        $nextSequence = ((int) $matches[1]) + 1;
    }

    return $prefix . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
}

function emarioh_inquiry_datetime_iso(?string $value): string
{
    $normalizedValue = trim((string) $value);

    if ($normalizedValue === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($normalizedValue))->format(DATE_ATOM);
    } catch (Throwable $throwable) {
        return $normalizedValue;
    }
}

function emarioh_map_website_inquiry_row(array $row): array
{
    $status = strtolower(trim((string) ($row['status'] ?? 'unread')));
    $status = in_array($status, ['unread', 'read', 'archived'], true) ? $status : 'unread';
    $name = emarioh_normalize_name((string) ($row['full_name'] ?? ''));
    $email = emarioh_trim_or_null(strtolower(trim((string) ($row['email'] ?? ''))));
    $category = trim((string) ($row['category'] ?? ''));

    return [
        'id' => (int) ($row['id'] ?? 0),
        'reference' => trim((string) ($row['reference'] ?? '')),
        'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
        'name' => $name !== '' ? $name : 'Guest Inquiry',
        'email' => $email,
        'mobile' => emarioh_trim_or_null((string) ($row['mobile'] ?? '')),
        'category' => $category !== '' ? $category : 'General Inquiry',
        'source' => trim((string) ($row['source'] ?? '')) ?: 'Public Website',
        'subject' => emarioh_trim_or_null((string) ($row['subject'] ?? '')),
        'message' => trim((string) ($row['message'] ?? '')),
        'status' => $status,
        'submittedAt' => emarioh_inquiry_datetime_iso((string) ($row['submitted_at'] ?? '')),
        'readAt' => emarioh_inquiry_datetime_iso((string) ($row['read_at'] ?? '')),
    ];
}

function emarioh_find_website_inquiry_by_id(PDO $db, int $inquiryId): ?array
{
    if ($inquiryId < 1) {
        return null;
    }

    $statement = $db->prepare('SELECT * FROM website_inquiries WHERE id = :id LIMIT 1');
    $statement->execute([
        ':id' => $inquiryId,
    ]);

    $row = $statement->fetch();
    return is_array($row) ? emarioh_map_website_inquiry_row($row) : null;
}

function emarioh_fetch_website_inquiries(PDO $db, array $options = []): array
{
    $sql = 'SELECT * FROM website_inquiries';
    $where = [];
    $params = [];

    if (isset($options['inquiry_id'])) {
        $where[] = 'id = :inquiry_id';
        $params[':inquiry_id'] = (int) $options['inquiry_id'];
    }

    if (!empty($options['statuses']) && is_array($options['statuses'])) {
        $statusPlaceholders = [];

        foreach (array_values($options['statuses']) as $index => $status) {
            $placeholder = ':status_' . $index;
            $statusPlaceholders[] = $placeholder;
            $params[$placeholder] = (string) $status;
        }

        if ($statusPlaceholders !== []) {
            $where[] = 'status IN (' . implode(', ', $statusPlaceholders) . ')';
        }
    }

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY submitted_at DESC, id DESC';

    $limit = isset($options['limit']) ? (int) $options['limit'] : 0;

    if ($limit > 0) {
        $sql .= ' LIMIT ' . max(1, $limit);
    }

    $statement = $db->prepare($sql);
    $statement->execute($params);

    return array_map(
        static fn (array $row): array => emarioh_map_website_inquiry_row($row),
        $statement->fetchAll()
    );
}

function emarioh_create_website_inquiry(PDO $db, array $changes): array
{
    $fullName = emarioh_normalize_name((string) ($changes['full_name'] ?? ''));
    $email = strtolower(trim((string) ($changes['email'] ?? '')));
    $message = trim((string) ($changes['message'] ?? ''));
    $source = trim((string) ($changes['source'] ?? '')) ?: 'Public Website';
    $subject = emarioh_trim_or_null((string) ($changes['subject'] ?? ''));
    $category = trim((string) ($changes['category'] ?? ''));
    $mobile = emarioh_trim_or_null((string) ($changes['mobile'] ?? ''));
    $userId = isset($changes['user_id']) ? (int) $changes['user_id'] : 0;
    $reference = emarioh_generate_website_inquiry_reference($db);

    if ($mobile !== null) {
        $normalizedMobile = emarioh_normalize_mobile($mobile);
        $mobile = emarioh_is_valid_mobile($normalizedMobile) ? $normalizedMobile : $mobile;
    }

    $db->prepare('
        INSERT INTO website_inquiries (
            reference,
            user_id,
            full_name,
            email,
            mobile,
            category,
            source,
            subject,
            message,
            status
        ) VALUES (
            :reference,
            :user_id,
            :full_name,
            :email,
            :mobile,
            :category,
            :source,
            :subject,
            :message,
            :status
        )
    ')->execute([
        ':reference' => $reference,
        ':user_id' => $userId > 0 ? $userId : null,
        ':full_name' => $fullName,
        ':email' => $email,
        ':mobile' => $mobile,
        ':category' => $category !== '' ? $category : emarioh_infer_inquiry_category($message),
        ':source' => $source,
        ':subject' => $subject,
        ':message' => $message,
        ':status' => 'unread',
    ]);

    $inquiryId = (int) $db->lastInsertId();
    $inquiry = emarioh_find_website_inquiry_by_id($db, $inquiryId);

    if ($inquiry === null) {
        throw new RuntimeException('The inquiry could not be loaded after saving.');
    }

    return $inquiry;
}

function emarioh_update_website_inquiry_status(PDO $db, int $inquiryId, string $status): ?array
{
    if ($inquiryId < 1) {
        return null;
    }

    $normalizedStatus = strtolower(trim($status));

    if (!in_array($normalizedStatus, ['unread', 'read', 'archived'], true)) {
        return null;
    }

    $db->prepare('
        UPDATE website_inquiries
        SET
            status = :status,
            read_at = CASE
                WHEN :status = \'read\' THEN COALESCE(read_at, CURRENT_TIMESTAMP)
                WHEN :status = \'unread\' THEN NULL
                ELSE read_at
            END
        WHERE id = :id
        LIMIT 1
    ')->execute([
        ':status' => $normalizedStatus,
        ':id' => $inquiryId,
    ]);

    return emarioh_find_website_inquiry_by_id($db, $inquiryId);
}

function emarioh_delete_website_inquiry(PDO $db, int $inquiryId): bool
{
    if ($inquiryId < 1) {
        return false;
    }

    $statement = $db->prepare('DELETE FROM website_inquiries WHERE id = :id LIMIT 1');
    $statement->execute([
        ':id' => $inquiryId,
    ]);

    return $statement->rowCount() > 0;
}

function emarioh_find_client_profile(PDO $db, int $userId): ?array
{
    $statement = $db->prepare('SELECT * FROM client_profiles WHERE user_id = :user_id LIMIT 1');
    $statement->execute([
        ':user_id' => $userId,
    ]);

    $profile = $statement->fetch();
    return is_array($profile) ? $profile : null;
}

function emarioh_upsert_client_profile(PDO $db, int $userId, ?string $email = null, ?string $alternateContact = null): void
{
    $db->prepare('
        INSERT INTO client_profiles (user_id, email, alternate_contact, last_activity_at)
        VALUES (:user_id, :email, :alternate_contact, NOW())
        ON DUPLICATE KEY UPDATE
            email = VALUES(email),
            alternate_contact = VALUES(alternate_contact),
            last_activity_at = VALUES(last_activity_at)
    ')->execute([
        ':user_id' => $userId,
        ':email' => emarioh_trim_or_null($email),
        ':alternate_contact' => emarioh_trim_or_null($alternateContact),
    ]);
}

function emarioh_booking_status_label(string $status): string
{
    return match ($status) {
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
        default => 'Pending Review',
    };
}

function emarioh_booking_admin_status_class(string $status): string
{
    return match ($status) {
        'approved' => 'approved',
        'rejected' => 'rejected',
        'cancelled' => 'inactive',
        'completed' => 'completed',
        default => 'pending',
    };
}

function emarioh_booking_client_status_class(string $status): string
{
    return match ($status) {
        'approved', 'completed' => 'confirmed',
        'rejected' => 'rejected',
        'cancelled' => 'cancelled',
        default => 'pending',
    };
}

function emarioh_booking_filter_key(string $status): string
{
    return match ($status) {
        'approved', 'completed' => 'approved',
        'rejected' => 'rejected',
        'cancelled' => 'cancelled',
        default => 'pending',
    };
}

function emarioh_booking_venue_option_label(string $venueOption): string
{
    return $venueOption === 'emarioh' ? 'Emarioh Venue' : 'Client Venue';
}

function emarioh_generate_booking_reference(PDO $db): string
{
    $prefix = 'BK-' . date('Ymd') . '-';
    $statement = $db->prepare('
        SELECT reference
        FROM booking_requests
        WHERE reference LIKE :reference_prefix
        ORDER BY reference DESC
        LIMIT 1
    ');
    $statement->execute([
        ':reference_prefix' => $prefix . '%',
    ]);

    $latestReference = (string) ($statement->fetchColumn() ?: '');
    $nextSequence = 1;

    if ($latestReference !== '' && preg_match('/-(\d{3})$/', $latestReference, $matches) === 1) {
        $nextSequence = ((int) $matches[1]) + 1;
    }

    return $prefix . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
}

function emarioh_find_service_package_by_code(PDO $db, string $packageCode): ?array
{
    $normalizedCode = trim($packageCode);

    if ($normalizedCode === '') {
        return null;
    }

    $statement = $db->prepare('SELECT * FROM service_packages WHERE package_code = :package_code LIMIT 1');
    $statement->execute([
        ':package_code' => $normalizedCode,
    ]);

    $package = $statement->fetch();

    if (!is_array($package)) {
        return null;
    }

    return emarioh_hydrate_service_package_record($db, $package);
}

function emarioh_find_service_package_by_id(PDO $db, int $packageId): ?array
{
    if ($packageId < 1) {
        return null;
    }

    $statement = $db->prepare('SELECT * FROM service_packages WHERE id = :id LIMIT 1');
    $statement->execute([
        ':id' => $packageId,
    ]);

    $package = $statement->fetch();

    if (!is_array($package)) {
        return null;
    }

    return emarioh_hydrate_service_package_record($db, $package);
}

function emarioh_find_service_package_for_booking(PDO $db, array $booking): ?array
{
    $packageId = (int) ($booking['package_id'] ?? 0);

    if ($packageId > 0) {
        $package = emarioh_find_service_package_by_id($db, $packageId);

        if ($package !== null) {
            return $package;
        }
    }

    $packageSelectionValue = trim((string) ($booking['package_selection_value'] ?? ''));

    if ($packageSelectionValue === '') {
        return null;
    }

    $packageCode = trim((string) explode('::', $packageSelectionValue, 2)[0]);
    return $packageCode !== '' ? emarioh_find_service_package_by_code($db, $packageCode) : null;
}

function emarioh_fetch_service_package_tier_rows(PDO $db, int $packageId): array
{
    if ($packageId < 1) {
        return [];
    }

    $statement = $db->prepare('
        SELECT tier_label, price_label, down_payment_amount
        FROM package_pricing_tiers
        WHERE package_id = :package_id
        ORDER BY sort_order ASC, id ASC
    ');
    $statement->execute([
        ':package_id' => $packageId,
    ]);

    $tierRows = $statement->fetchAll();
    return is_array($tierRows) ? $tierRows : [];
}

function emarioh_hydrate_service_package_record(PDO $db, array $package): array
{
    $packageId = (int) ($package['id'] ?? 0);
    $tierRows = emarioh_fetch_service_package_tier_rows($db, $packageId);

    if ($tierRows === []) {
        return $package;
    }

    $pricingTiers = [];
    $downPaymentTiers = [];

    foreach ($tierRows as $tierRow) {
        $tierLabel = trim((string) ($tierRow['tier_label'] ?? ''));
        $priceLabel = trim((string) ($tierRow['price_label'] ?? ''));
        $downPaymentAmount = trim((string) ($tierRow['down_payment_amount'] ?? ''));

        if ($tierLabel === '' || $priceLabel === '') {
            continue;
        }

        $pricingTiers[] = [
            'label' => $tierLabel,
            'price' => $priceLabel,
        ];
        $downPaymentTiers[] = [
            'label' => $tierLabel,
            'amount' => $downPaymentAmount,
        ];
    }

    if ($pricingTiers === []) {
        return $package;
    }

    $package['pricing_tiers'] = $pricingTiers;
    $package['down_payment_tiers'] = $downPaymentTiers;

    if (count($downPaymentTiers) === 1) {
        $singleTierAmount = trim((string) ($downPaymentTiers[0]['amount'] ?? ''));

        if ($singleTierAmount !== '') {
            $package['down_payment_amount'] = $singleTierAmount;
        }
    }

    return $package;
}

function emarioh_parse_guest_count_hint(string $value): ?int
{
    if (preg_match('/(\d{1,4})/', $value, $matches) !== 1) {
        return null;
    }

    $guestCount = (int) ($matches[1] ?? 0);
    return $guestCount > 0 ? $guestCount : null;
}

function emarioh_fetch_service_package_catalog(PDO $db): array
{
    $packages = $db->query('
        SELECT *
        FROM service_packages
        ORDER BY sort_order ASC, id ASC
    ')->fetchAll();

    if (!is_array($packages) || $packages === []) {
        return [];
    }

    $packageIds = array_values(array_map(
        static fn (array $package): int => (int) ($package['id'] ?? 0),
        array_filter($packages, static fn (array $package): bool => (int) ($package['id'] ?? 0) > 0)
    ));

    if ($packageIds === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($packageIds), '?'));

    $tierStatement = $db->prepare("
        SELECT *
        FROM package_pricing_tiers
        WHERE package_id IN ($placeholders)
        ORDER BY package_id ASC, sort_order ASC, id ASC
    ");
    $tierStatement->execute($packageIds);
    $tierRows = $tierStatement->fetchAll();

    $tagStatement = $db->prepare("
        SELECT *
        FROM package_tags
        WHERE package_id IN ($placeholders)
        ORDER BY package_id ASC, sort_order ASC, id ASC
    ");
    $tagStatement->execute($packageIds);
    $tagRows = $tagStatement->fetchAll();

    $inclusionStatement = $db->prepare("
        SELECT *
        FROM package_inclusions
        WHERE package_id IN ($placeholders)
        ORDER BY package_id ASC, sort_order ASC, id ASC
    ");
    $inclusionStatement->execute($packageIds);
    $inclusionRows = $inclusionStatement->fetchAll();

    $tiersByPackageId = [];
    foreach ($tierRows as $tierRow) {
        $packageId = (int) ($tierRow['package_id'] ?? 0);

        if ($packageId < 1) {
            continue;
        }

        $tiersByPackageId[$packageId][] = [
            'label' => trim((string) ($tierRow['tier_label'] ?? '')),
            'price' => trim((string) ($tierRow['price_label'] ?? '')),
            'amount' => trim((string) ($tierRow['down_payment_amount'] ?? '')),
        ];
    }

    $tagsByPackageId = [];
    foreach ($tagRows as $tagRow) {
        $packageId = (int) ($tagRow['package_id'] ?? 0);
        $tagText = trim((string) ($tagRow['tag_text'] ?? ''));

        if ($packageId < 1 || $tagText === '') {
            continue;
        }

        $tagsByPackageId[$packageId][] = $tagText;
    }

    $inclusionsByPackageId = [];
    foreach ($inclusionRows as $inclusionRow) {
        $packageId = (int) ($inclusionRow['package_id'] ?? 0);
        $inclusionText = trim((string) ($inclusionRow['inclusion_text'] ?? ''));

        if ($packageId < 1 || $inclusionText === '') {
            continue;
        }

        $inclusionsByPackageId[$packageId][] = $inclusionText;
    }

    return array_values(array_map(static function (array $package) use ($tiersByPackageId, $tagsByPackageId, $inclusionsByPackageId): array {
        $packageId = (int) ($package['id'] ?? 0);
        $tierRows = $tiersByPackageId[$packageId] ?? [];
        $pricingTiers = [];
        $downPaymentTiers = [];

        foreach ($tierRows as $tierRow) {
            $tierLabel = trim((string) ($tierRow['label'] ?? ''));
            $priceLabel = trim((string) ($tierRow['price'] ?? ''));
            $downPaymentAmount = trim((string) ($tierRow['amount'] ?? ''));

            if ($tierLabel === '' || $priceLabel === '') {
                continue;
            }

            $pricingTiers[] = [
                'label' => $tierLabel,
                'price' => $priceLabel,
            ];

            $downPaymentTiers[] = [
                'label' => $tierLabel,
                'amount' => $downPaymentAmount,
            ];
        }

        $packageDownPaymentAmount = trim((string) ($package['down_payment_amount'] ?? ''));

        if (count($downPaymentTiers) === 1) {
            $singleTierAmount = trim((string) ($downPaymentTiers[0]['amount'] ?? ''));

            if ($singleTierAmount !== '') {
                $packageDownPaymentAmount = $singleTierAmount;
            }
        }

        return [
            'id' => trim((string) ($package['package_code'] ?? '')),
            'group' => trim((string) ($package['group_key'] ?? 'per-head')),
            'name' => trim((string) ($package['name'] ?? '')),
            'category' => trim((string) ($package['category_label'] ?? '')),
            'guestLabel' => trim((string) ($package['guest_label'] ?? '')),
            'rateLabel' => trim((string) ($package['rate_label'] ?? '')),
            'allowDownPayment' => (int) ($package['allow_down_payment'] ?? 0) === 1,
            'downPaymentAmount' => $packageDownPaymentAmount,
            'downPaymentTiers' => $downPaymentTiers,
            'status' => trim((string) ($package['status'] ?? 'review')),
            'description' => trim((string) ($package['description'] ?? '')),
            'tags' => $tagsByPackageId[$packageId] ?? [],
            'pricingTiers' => $pricingTiers,
            'inclusions' => $inclusionsByPackageId[$packageId] ?? [],
        ];
    }, $packages));
}

function emarioh_client_invoice_reference(string $bookingReference): string
{
    $normalizedReference = trim($bookingReference);

    if ($normalizedReference === '') {
        return 'INV-TBA';
    }

    if (str_starts_with($normalizedReference, 'INV-')) {
        return $normalizedReference;
    }

    if (preg_match('/^(?:BK|REQ)-(.*)$/i', $normalizedReference, $matches) === 1) {
        return 'INV-' . trim((string) ($matches[1] ?? ''));
    }

    return 'INV-' . $normalizedReference;
}

function emarioh_payment_settings_defaults(): array
{
    return [
        'payment_gateway' => 'PayMongo',
        'active_method' => 'PayMongo QRPh',
        'accepted_wallets_label' => 'Any QRPh-supported e-wallet or banking app',
        'allow_full_payment' => 1,
        'balance_due_rule' => '3 days before event',
        'receipt_requirement' => 'receipt_required',
        'confirmation_rule' => 'verified_down_payment',
        'support_mobile' => null,
        'instruction_text' => 'Use the active PayMongo QRPh checkout in your invoice and complete the payment there.',
    ];
}

function emarioh_fetch_payment_settings(PDO $db): array
{
    static $cachedSettings = null;

    if (is_array($cachedSettings)) {
        return $cachedSettings;
    }

    $defaults = emarioh_payment_settings_defaults();
    $statement = $db->query('SELECT * FROM payment_settings WHERE id = 1 LIMIT 1');
    $settings = $statement->fetch();

    if (!is_array($settings)) {
        $cachedSettings = $defaults;
        return $cachedSettings;
    }

    $cachedSettings = array_replace($defaults, $settings);
    return $cachedSettings;
}

function emarioh_parse_money_amount(string $value): float
{
    $normalizedValue = trim($value);

    if ($normalizedValue === '' || str_contains($normalizedValue, '|')) {
        return 0.0;
    }

    $normalizedValue = preg_replace('/[^0-9.,]/', '', $normalizedValue) ?? '';

    if ($normalizedValue === '') {
        return 0.0;
    }

    $normalizedValue = str_replace(',', '', $normalizedValue);
    $parsedValue = (float) $normalizedValue;

    return $parsedValue > 0 ? $parsedValue : 0.0;
}

function emarioh_format_money_amount(float $amount): string
{
    return 'PHP ' . number_format(max(0, $amount), 2);
}

function emarioh_format_log_datetime(?string $dateTimeValue): string
{
    $normalizedValue = trim((string) $dateTimeValue);

    if ($normalizedValue === '') {
        return 'Date not provided';
    }

    try {
        $date = new DateTimeImmutable($normalizedValue);
    } catch (Throwable $exception) {
        return 'Date not provided';
    }

    return $date->format('F j, Y | g:i A');
}

function emarioh_resolve_booking_payment_amount_value(array $booking, ?array $package = null): float
{
    $downPaymentAmountValue = emarioh_resolve_booking_down_payment_amount_value($booking, $package);

    if ($downPaymentAmountValue > 0) {
        return $downPaymentAmountValue;
    }

    return emarioh_resolve_booking_full_amount_value($booking, $package);
}

function emarioh_resolve_package_down_payment_amount_label(?array $package, string $tierLabel = ''): string
{
    if ($package === null) {
        return '';
    }

    $normalizedTierLabel = strtolower(trim($tierLabel));
    $downPaymentTiers = is_array($package['down_payment_tiers'] ?? null)
        ? $package['down_payment_tiers']
        : [];

    if ($normalizedTierLabel !== '' && $downPaymentTiers !== []) {
        foreach ($downPaymentTiers as $tierRow) {
            if (strtolower(trim((string) ($tierRow['label'] ?? ''))) !== $normalizedTierLabel) {
                continue;
            }

            $tierAmount = trim((string) ($tierRow['amount'] ?? ''));

            if ($tierAmount !== '') {
                return $tierAmount;
            }

            break;
        }
    }

    if ($normalizedTierLabel === '' && count($downPaymentTiers) === 1) {
        $singleTierAmount = trim((string) ($downPaymentTiers[0]['amount'] ?? ''));

        if ($singleTierAmount !== '') {
            return $singleTierAmount;
        }
    }

    return trim((string) ($package['down_payment_amount'] ?? ''));
}

function emarioh_resolve_booking_down_payment_amount_label(array $booking, ?array $package = null): string
{
    $bookingTierLabel = trim((string) ($booking['package_tier_label'] ?? ''));

    if ($package !== null) {
        $packageAllowsDownPayment = (int) ($package['allow_down_payment'] ?? 0) === 1;

        if (!$packageAllowsDownPayment) {
            return '';
        }

        $packageDownPaymentAmount = emarioh_resolve_package_down_payment_amount_label($package, $bookingTierLabel);

        if ($packageDownPaymentAmount !== '') {
            return $packageDownPaymentAmount;
        }
    }

    $bookingAllowsDownPayment = (int) ($booking['package_allows_down_payment'] ?? 0) === 1;
    $bookingDownPaymentAmount = trim((string) ($booking['package_down_payment_amount'] ?? ''));

    if ($bookingAllowsDownPayment && $bookingDownPaymentAmount !== '') {
        return $bookingDownPaymentAmount;
    }

    return '';
}

function emarioh_resolve_booking_down_payment_amount_value(array $booking, ?array $package = null): float
{
    $downPaymentAmount = emarioh_resolve_booking_down_payment_amount_label($booking, $package);

    if ($downPaymentAmount !== '') {
        return emarioh_parse_money_amount($downPaymentAmount);
    }

    return 0.0;
}

function emarioh_resolve_booking_full_amount_value(array $booking, ?array $package = null): float
{
    $tierPrice = trim((string) ($booking['package_tier_price'] ?? ''));
    $rateSource = $tierPrice !== ''
        ? $tierPrice
        : trim((string) ($package['rate_label'] ?? ''));
    $baseAmount = emarioh_parse_money_amount($rateSource);

    if ($baseAmount <= 0) {
        return 0.0;
    }

    $groupKey = strtolower(trim((string) ($package['group_key'] ?? '')));
    $normalizedRateSource = strtolower($rateSource);
    $isPerHeadRate = $groupKey === 'per-head'
        || str_contains($normalizedRateSource, '/head')
        || str_contains($normalizedRateSource, 'per head')
        || preg_match('/\bhead\b/i', $rateSource) === 1;

    if (!$isPerHeadRate) {
        return $baseAmount;
    }

    $guestCount = max(1, (int) ($booking['guest_count'] ?? 0));
    return $baseAmount * $guestCount;
}

function emarioh_resolve_booking_payment_plan(
    array $booking,
    ?array $package = null,
    ?array $invoice = null,
    bool $allowFullPayment = true,
    string $requestedOption = ''
): array {
    $fullAmountValue = max(0, emarioh_resolve_booking_full_amount_value($booking, $package));
    $downPaymentAmountValue = max(0, emarioh_resolve_booking_down_payment_amount_value($booking, $package));
    $amountPaidValue = max(0, (float) ($invoice['amount_paid'] ?? 0));
    $remainingBalanceValue = max(0, $fullAmountValue - $amountPaidValue);
    $packageAllowsDownPayment = $downPaymentAmountValue > 0 && $downPaymentAmountValue < $fullAmountValue;
    $normalizedRequestedOption = strtolower(trim($requestedOption));
    $selectedOption = 'full_payment';
    $selectedLabel = 'Full Payment';
    $chargeAmountValue = $remainingBalanceValue > 0 ? $remainingBalanceValue : $fullAmountValue;
    $helpText = 'Settle the full amount to complete your booking payment.';
    $availableOptions = [];

    if ($amountPaidValue > 0.00001 && $remainingBalanceValue > 0.00001) {
        $selectedOption = 'remaining_balance';
        $selectedLabel = 'Remaining Balance';
        $chargeAmountValue = $remainingBalanceValue;
        $helpText = 'Pay the remaining balance to complete your booking payment.';
        $availableOptions[] = 'remaining_balance';
    } elseif ($packageAllowsDownPayment && $allowFullPayment) {
        $availableOptions = ['down_payment', 'full_payment'];
        $selectedOption = $normalizedRequestedOption === 'full_payment'
            ? 'full_payment'
            : 'down_payment';
        $selectedLabel = $selectedOption === 'full_payment' ? 'Full Payment' : 'Down Payment';
        $chargeAmountValue = $selectedOption === 'full_payment'
            ? $fullAmountValue
            : $downPaymentAmountValue;
        $helpText = $selectedOption === 'full_payment'
            ? 'Pay the full amount now to finish your booking payment in one checkout.'
            : 'Pay the reservation amount now. You can settle the remaining balance later.';
    } elseif ($packageAllowsDownPayment) {
        $availableOptions[] = 'down_payment';
        $selectedOption = 'down_payment';
        $selectedLabel = 'Down Payment';
        $chargeAmountValue = $downPaymentAmountValue;
        $helpText = 'Pay the reservation amount now. You can settle the remaining balance later.';
    } else {
        $availableOptions[] = 'full_payment';
        $selectedOption = 'full_payment';
        $selectedLabel = $remainingBalanceValue > 0 && $amountPaidValue > 0.00001
            ? 'Remaining Balance'
            : 'Full Payment';
        $chargeAmountValue = $remainingBalanceValue > 0 ? $remainingBalanceValue : $fullAmountValue;
        $helpText = $amountPaidValue > 0.00001 && $remainingBalanceValue > 0.00001
            ? 'Pay the remaining balance to complete your booking payment.'
            : 'Settle the full amount to complete your booking payment.';
    }

    if ($remainingBalanceValue > 0.00001) {
        $chargeAmountValue = min(max($chargeAmountValue, 0), $remainingBalanceValue);
    } else {
        $chargeAmountValue = 0.0;
    }

    return [
        'full_amount_value' => $fullAmountValue,
        'down_payment_amount_value' => $downPaymentAmountValue,
        'amount_paid_value' => $amountPaidValue,
        'remaining_balance_value' => $remainingBalanceValue,
        'package_allows_down_payment' => $packageAllowsDownPayment,
        'allow_full_payment' => $allowFullPayment,
        'available_options' => $availableOptions,
        'selected_option' => $selectedOption,
        'selected_option_label' => $selectedLabel,
        'charge_amount_value' => $chargeAmountValue,
        'help_text' => $helpText,
    ];
}

function emarioh_resolve_payment_due_date(?string $eventDateValue, string $balanceDueRule): ?string
{
    $normalizedEventDate = trim((string) $eventDateValue);
    $normalizedRule = trim($balanceDueRule);

    if ($normalizedEventDate === '') {
        return null;
    }

    try {
        $eventDate = new DateTimeImmutable($normalizedEventDate);
    } catch (Throwable $exception) {
        return null;
    }

    if ($normalizedRule !== '' && preg_match('/(\d+)\s+days?\s+before\s+event/i', $normalizedRule, $matches) === 1) {
        $daysBefore = max(0, (int) ($matches[1] ?? 0));
        return $eventDate->modify(sprintf('-%d days', $daysBefore))->format('Y-m-d');
    }

    if ($normalizedRule !== '' && preg_match('/event\s*date|on\s*event/i', $normalizedRule) === 1) {
        return $eventDate->format('Y-m-d');
    }

    return null;
}

function emarioh_configured_app_url(): string
{
    $configuredAppUrl = rtrim(trim((string) (emarioh_config()['app']['url'] ?? '')), '/');

    if ($configuredAppUrl === '') {
        return '';
    }

    if (emarioh_should_ignore_configured_app_url_for_local_runtime($configuredAppUrl)) {
        return '';
    }

    return $configuredAppUrl;
}

function emarioh_app_force_https(): bool
{
    $forceHttps = (bool) (emarioh_config()['app']['force_https'] ?? false);

    if (!$forceHttps) {
        return false;
    }

    return emarioh_configured_app_url() !== '';
}

function emarioh_should_ignore_configured_app_url_for_local_runtime(string $configuredAppUrl): bool
{
    if (!emarioh_runtime_looks_local()) {
        return false;
    }

    $configuredHost = strtolower(trim((string) parse_url($configuredAppUrl, PHP_URL_HOST)));

    if ($configuredHost === '') {
        return false;
    }

    if (in_array($configuredHost, ['localhost', '127.0.0.1', '::1'], true)) {
        return false;
    }

    if (preg_match('/(?:^|\.)((test|local|localhost))$/', $configuredHost) === 1) {
        return false;
    }

    return !emarioh_ip_is_local_or_private($configuredHost);
}

function emarioh_transport_is_https(): bool
{
    $https = strtolower(trim((string) ($_SERVER['HTTPS'] ?? '')));

    if ($https !== '' && $https !== 'off' && $https !== '0') {
        return true;
    }

    $forwardedProto = strtolower(trim((string) (
        $_SERVER['HTTP_X_FORWARDED_PROTO']
        ?? $_SERVER['HTTP_X_FORWARDED_PROTOCOL']
        ?? ''
    )));

    if ($forwardedProto === 'https') {
        return true;
    }

    $forwardedSsl = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));

    if ($forwardedSsl === 'on' || $forwardedSsl === '1') {
        return true;
    }

    return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function emarioh_request_is_https(): bool
{
    if (emarioh_app_force_https()) {
        return true;
    }

    $configuredAppUrl = emarioh_configured_app_url();

    if ($configuredAppUrl !== '') {
        $configuredScheme = strtolower(trim((string) parse_url($configuredAppUrl, PHP_URL_SCHEME)));

        if ($configuredScheme === 'https') {
            return true;
        }
    }

    return emarioh_transport_is_https();
}

function emarioh_request_host(): string
{
    $host = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost'));

    if (str_contains($host, ',')) {
        $host = trim((string) explode(',', $host, 2)[0]);
    }

    if ($host === '') {
        $host = 'localhost';
    }

    return $host;
}

function emarioh_request_uri(): string
{
    $requestUri = trim((string) ($_SERVER['REQUEST_URI'] ?? ''));

    if ($requestUri !== '') {
        return $requestUri;
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    return $scriptName !== '' ? $scriptName : '/';
}

function emarioh_enforce_https_request(): void
{
    if (PHP_SAPI === 'cli' || headers_sent() || !emarioh_app_force_https() || emarioh_transport_is_https()) {
        return;
    }

    $configuredAppUrl = emarioh_configured_app_url();
    $host = emarioh_request_host();

    if ($configuredAppUrl !== '') {
        $configuredHost = trim((string) parse_url($configuredAppUrl, PHP_URL_HOST));
        $configuredPort = parse_url($configuredAppUrl, PHP_URL_PORT);

        if ($configuredHost !== '') {
            $host = $configuredHost;

            if (is_int($configuredPort) && $configuredPort > 0) {
                $host .= ':' . $configuredPort;
            }
        }
    }

    header('Location: https://' . $host . emarioh_request_uri(), true, 302);
    exit;
}

function emarioh_app_base_path(): string
{
    $configuredAppUrl = emarioh_configured_app_url();

    if ($configuredAppUrl !== '') {
        $configuredPath = trim((string) parse_url($configuredAppUrl, PHP_URL_PATH));

        if ($configuredPath !== '' && $configuredPath !== '/') {
            return '/' . trim($configuredPath, '/');
        }

        return '';
    }

    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $basePath = realpath(EMARIOH_BASE_PATH) ?: EMARIOH_BASE_PATH;

    if ($documentRoot && str_starts_with(strtolower($basePath), strtolower($documentRoot))) {
        $relativePath = str_replace('\\', '/', substr($basePath, strlen($documentRoot)));
        $normalizedPath = '/' . trim($relativePath, '/');
        return $normalizedPath === '/' ? '' : $normalizedPath;
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $segments = array_values(array_filter(explode('/', trim($scriptName, '/'))));

    if ($segments === []) {
        return '';
    }

    return '/' . trim((string) ($segments[0] ?? ''), '/');
}

function emarioh_app_base_url(): string
{
    $configuredAppUrl = emarioh_configured_app_url();

    if ($configuredAppUrl !== '') {
        return $configuredAppUrl;
    }

    $isHttps = emarioh_request_is_https();
    $scheme = $isHttps ? 'https' : 'http';
    $host = emarioh_request_host();

    return $scheme . '://' . $host . emarioh_app_base_path();
}

function emarioh_resolve_redirect_url(string $url): string
{
    $trimmedUrl = trim($url);

    if ($trimmedUrl === '' || emarioh_is_absolute_url($trimmedUrl) || str_starts_with($trimmedUrl, '#')) {
        return $trimmedUrl;
    }

    return emarioh_app_url(ltrim($trimmedUrl, '/'));
}

function emarioh_normalize_json_response_payload(array $payload): array
{
    if (isset($payload['redirect_url']) && is_string($payload['redirect_url'])) {
        $payload['redirect_url'] = emarioh_resolve_redirect_url((string) $payload['redirect_url']);
    }

    return $payload;
}

function emarioh_app_url(string $path = ''): string
{
    $baseUrl = rtrim(emarioh_app_base_url(), '/');
    $normalizedPath = ltrim($path, '/');

    if ($normalizedPath === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . $normalizedPath;
}

function emarioh_should_use_vendor_cdn(): bool
{
    static $shouldUseVendorCdn = null;

    if (is_bool($shouldUseVendorCdn)) {
        return $shouldUseVendorCdn;
    }

    $shouldUseVendorCdn = emarioh_env_bool('EMARIOH_USE_VENDOR_CDN', false);
    return $shouldUseVendorCdn;
}

function emarioh_render_vendor_head_assets(bool $includeBootstrapCss = true, bool $includeBootstrapIcons = true): string
{
    $tags = [];
    $tags[] = '<base href="' . htmlspecialchars(rtrim(emarioh_app_base_url(), '/') . '/', ENT_QUOTES, 'UTF-8') . '">';

    $tags[] = '<link rel="preconnect" href="https://fonts.googleapis.com">';
    $tags[] = '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    $tags[] = '<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

    if ($includeBootstrapCss) {
        $tags[] = '<link rel="stylesheet" href="' . htmlspecialchars(emarioh_app_url('assets/vendor/bootstrap/bootstrap.min.css'), ENT_QUOTES, 'UTF-8') . '">';
    }

    if ($includeBootstrapIcons) {
        $tags[] = '<link rel="stylesheet" href="' . htmlspecialchars(emarioh_app_url('assets/vendor/bootstrap-icons/bootstrap-icons.css'), ENT_QUOTES, 'UTF-8') . '">';
    }

    $tags[] = '<link rel="stylesheet" href="' . htmlspecialchars(emarioh_app_url('assets/css/vendor-fallback.css?v=20260419c'), ENT_QUOTES, 'UTF-8') . '">';

    return implode(PHP_EOL . '    ', $tags);
}

function emarioh_render_vendor_runtime_assets(bool $includeBootstrapBundle = false): string
{
    $tags = [];

    if ($includeBootstrapBundle) {
        $tags[] = '<script src="' . htmlspecialchars(emarioh_app_url('assets/vendor/bootstrap/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8') . '"></script>';
    }

    $tags[] = '<script src="' . htmlspecialchars(emarioh_app_url('assets/js/vendor-runtime.js?v=20260419a'), ENT_QUOTES, 'UTF-8') . '"></script>';

    return implode(PHP_EOL . '    ', $tags);
}

function emarioh_paymongo_config(): array
{
    $config = emarioh_config();
    $paymongoConfig = $config['paymongo'] ?? [];

    return [
        'enabled' => (bool) ($paymongoConfig['enabled'] ?? false),
        'secret_key' => trim((string) ($paymongoConfig['secret_key'] ?? '')),
        'public_key' => trim((string) ($paymongoConfig['public_key'] ?? '')),
        'webhook_secret' => trim((string) ($paymongoConfig['webhook_secret'] ?? '')),
        'api_base' => rtrim((string) ($paymongoConfig['api_base'] ?? 'https://api.paymongo.com/v1'), '/'),
        'timeout_seconds' => max(5, (int) ($paymongoConfig['timeout_seconds'] ?? 20)),
    ];
}

function emarioh_paymongo_is_enabled(): bool
{
    return (bool) (emarioh_paymongo_config()['enabled'] ?? false);
}

function emarioh_paymongo_has_secret_key(): bool
{
    return trim((string) (emarioh_paymongo_config()['secret_key'] ?? '')) !== '';
}

function emarioh_paymongo_is_ready(): bool
{
    $config = emarioh_paymongo_config();

    return (bool) ($config['enabled'] ?? false)
        && trim((string) ($config['secret_key'] ?? '')) !== '';
}

function emarioh_paymongo_error_message(array $responseBody, string $fallbackMessage): string
{
    $errors = $responseBody['errors'] ?? null;

    if (is_array($errors) && isset($errors[0]) && is_array($errors[0])) {
        $detail = trim((string) ($errors[0]['detail'] ?? ''));

        if ($detail !== '') {
            return $detail;
        }

        $code = trim((string) ($errors[0]['code'] ?? ''));

        if ($code !== '') {
            return $code;
        }
    }

    $message = trim((string) ($responseBody['message'] ?? ''));
    return $message !== '' ? $message : $fallbackMessage;
}

function emarioh_paymongo_request(string $method, string $path, ?array $payload = null): array
{
    $config = emarioh_paymongo_config();
    $secretKey = trim((string) ($config['secret_key'] ?? ''));

    if ($secretKey === '') {
        throw new RuntimeException('PayMongo secret key is not configured yet.');
    }

    $response = emarioh_http_request_json(
        $method,
        $config['api_base'] . '/' . ltrim($path, '/'),
        [
            'Authorization: Basic ' . base64_encode($secretKey . ':'),
        ],
        $payload,
        (int) ($config['timeout_seconds'] ?? 20)
    );

    $decodedBody = [];

    if (($response['body'] ?? '') !== '') {
        try {
            $decoded = json_decode((string) $response['body'], true, 512, JSON_THROW_ON_ERROR);
            $decodedBody = is_array($decoded) ? $decoded : [];
        } catch (Throwable $exception) {
            $decodedBody = [];
        }
    }

    $statusCode = (int) ($response['status_code'] ?? 0);

    if ($statusCode < 200 || $statusCode >= 300) {
        if (!empty($response['error'])) {
            throw new RuntimeException((string) $response['error'], $statusCode);
        }

        throw new RuntimeException(emarioh_paymongo_error_message(
            $decodedBody,
            'PayMongo request failed. Please try again in a moment.'
        ), $statusCode);
    }

    return $decodedBody;
}

function emarioh_resolve_client_portal_amount_due(array $booking, ?array $package = null): ?string
{
    $amountValue = emarioh_resolve_booking_payment_amount_value($booking, $package);

    if ($amountValue > 0) {
        return emarioh_format_money_amount($amountValue);
    }

    $tierPrice = trim((string) ($booking['package_tier_price'] ?? ''));

    if ($tierPrice !== '') {
        return $tierPrice;
    }

    $rateLabel = trim((string) ($package['rate_label'] ?? ''));
    return $rateLabel !== '' ? $rateLabel : null;
}

function emarioh_find_payment_invoice_by_booking(PDO $db, int $bookingId): ?array
{
    if ($bookingId < 1) {
        return null;
    }

    $statement = $db->prepare('
        SELECT *
        FROM payment_invoices
        WHERE booking_id = :booking_id
        ORDER BY id DESC
        LIMIT 1
    ');
    $statement->execute([
        ':booking_id' => $bookingId,
    ]);

    $invoice = $statement->fetch();
    return is_array($invoice) ? $invoice : null;
}

function emarioh_find_payment_invoice_by_number(PDO $db, string $invoiceNumber): ?array
{
    $normalizedNumber = trim($invoiceNumber);

    if ($normalizedNumber === '') {
        return null;
    }

    $statement = $db->prepare('SELECT * FROM payment_invoices WHERE invoice_number = :invoice_number LIMIT 1');
    $statement->execute([
        ':invoice_number' => $normalizedNumber,
    ]);

    $invoice = $statement->fetch();
    return is_array($invoice) ? $invoice : null;
}

function emarioh_find_payment_invoice_by_checkout_session(PDO $db, string $checkoutSessionId): ?array
{
    $normalizedSessionId = trim($checkoutSessionId);

    if ($normalizedSessionId === '') {
        return null;
    }

    $statement = $db->prepare('
        SELECT *
        FROM payment_invoices
        WHERE gateway_checkout_session_id = :gateway_checkout_session_id
        LIMIT 1
    ');
    $statement->execute([
        ':gateway_checkout_session_id' => $normalizedSessionId,
    ]);

    $invoice = $statement->fetch();
    return is_array($invoice) ? $invoice : null;
}

function emarioh_payment_receipt_reference(string $invoiceNumber): string
{
    $normalizedInvoiceNumber = trim($invoiceNumber);

    if ($normalizedInvoiceNumber === '') {
        return 'RCT-TBA';
    }

    if (str_starts_with($normalizedInvoiceNumber, 'RCT-')) {
        return $normalizedInvoiceNumber;
    }

    if (str_starts_with($normalizedInvoiceNumber, 'INV-')) {
        return 'RCT-' . substr($normalizedInvoiceNumber, 4);
    }

    return 'RCT-' . $normalizedInvoiceNumber;
}

function emarioh_find_payment_receipt_by_invoice(PDO $db, int $invoiceId): ?array
{
    if ($invoiceId < 1) {
        return null;
    }

    $statement = $db->prepare('
        SELECT *
        FROM payment_receipts
        WHERE invoice_id = :invoice_id
        ORDER BY id DESC
        LIMIT 1
    ');
    $statement->execute([
        ':invoice_id' => $invoiceId,
    ]);

    $receipt = $statement->fetch();
    return is_array($receipt) ? $receipt : null;
}

function emarioh_upsert_system_payment_receipt(
    PDO $db,
    array $invoice,
    ?array $booking = null,
    array $paymentSummary = []
): ?array {
    $invoiceId = (int) ($invoice['id'] ?? 0);
    $bookingId = (int) ($invoice['booking_id'] ?? 0);

    if ($invoiceId < 1 || $bookingId < 1) {
        return null;
    }

    $booking = $booking ?? emarioh_find_booking_by_id($db, $bookingId);

    if ($booking === null) {
        return null;
    }

    $existingReceipt = emarioh_find_payment_receipt_by_invoice($db, $invoiceId);
    $receiptReference = emarioh_payment_receipt_reference((string) ($invoice['invoice_number'] ?? ''));
    $amountReported = max(0, (float) ($paymentSummary['amount_paid'] ?? 0));

    if ($amountReported <= 0) {
        $amountReported = max(0, (float) ($invoice['amount_paid'] ?? 0));
    }

    if ($amountReported <= 0) {
        $amountReported = max(0, (float) ($invoice['amount_due'] ?? 0));
    }
    $senderName = trim((string) ($booking['primary_contact'] ?? ''));
    $senderMobile = trim((string) ($booking['primary_mobile'] ?? ''));
    $receiptNotes = 'System-generated receipt after PayMongo confirmed the payment.';

    if ($existingReceipt === null) {
        $db->prepare('
            INSERT INTO payment_receipts (
                invoice_id,
                booking_id,
                uploaded_by_user_id,
                original_file_name,
                stored_file_path,
                receipt_reference,
                sender_name,
                sender_mobile,
                amount_reported,
                notes,
                status,
                reviewed_at,
                reviewed_by_user_id
            ) VALUES (
                :invoice_id,
                :booking_id,
                NULL,
                NULL,
                NULL,
                :receipt_reference,
                :sender_name,
                :sender_mobile,
                :amount_reported,
                :notes,
                :status,
                :reviewed_at,
                NULL
            )
        ')->execute([
            ':invoice_id' => $invoiceId,
            ':booking_id' => $bookingId,
            ':receipt_reference' => $receiptReference,
            ':sender_name' => emarioh_trim_or_null($senderName),
            ':sender_mobile' => emarioh_trim_or_null($senderMobile),
            ':amount_reported' => number_format($amountReported, 2, '.', ''),
            ':notes' => $receiptNotes,
            ':status' => 'verified',
            ':reviewed_at' => date('Y-m-d H:i:s'),
        ]);
    } else {
        $db->prepare('
            UPDATE payment_receipts
            SET receipt_reference = :receipt_reference,
                sender_name = :sender_name,
                sender_mobile = :sender_mobile,
                amount_reported = :amount_reported,
                notes = :notes,
                status = :status,
                reviewed_at = COALESCE(reviewed_at, :reviewed_at),
                reviewed_by_user_id = NULL
            WHERE id = :id
        ')->execute([
            ':receipt_reference' => $receiptReference,
            ':sender_name' => emarioh_trim_or_null($senderName),
            ':sender_mobile' => emarioh_trim_or_null($senderMobile),
            ':amount_reported' => number_format($amountReported, 2, '.', ''),
            ':notes' => $receiptNotes,
            ':status' => 'verified',
            ':reviewed_at' => date('Y-m-d H:i:s'),
            ':id' => (int) $existingReceipt['id'],
        ]);
    }

    return emarioh_find_payment_receipt_by_invoice($db, $invoiceId);
}

function emarioh_fetch_payment_logs_for_invoice(PDO $db, int $invoiceId): array
{
    if ($invoiceId < 1) {
        return [];
    }

    $statement = $db->prepare('
        SELECT *
        FROM payment_logs
        WHERE invoice_id = :invoice_id
        ORDER BY created_at ASC, id ASC
    ');
    $statement->execute([
        ':invoice_id' => $invoiceId,
    ]);

    $rows = $statement->fetchAll();
    return is_array($rows) ? $rows : [];
}

function emarioh_sanitize_payment_log_text(?string $value): string
{
    $normalizedValue = str_replace(["\r", "\n", '^', '|'], ' ', trim((string) $value));
    $normalizedValue = preg_replace('/\s+/', ' ', $normalizedValue) ?? '';
    return trim($normalizedValue);
}

function emarioh_serialize_payment_logs(array $logs): string
{
    $serializedEntries = [];

    foreach ($logs as $log) {
        $serializedEntries[] = implode('^', [
            emarioh_sanitize_payment_log_text(emarioh_format_log_datetime((string) ($log['created_at'] ?? ''))),
            emarioh_sanitize_payment_log_text((string) ($log['title'] ?? '')),
            emarioh_sanitize_payment_log_text((string) ($log['meta_label'] ?? '')),
            emarioh_sanitize_payment_log_text((string) ($log['status_class'] ?? '')),
            emarioh_sanitize_payment_log_text((string) ($log['status_label'] ?? '')),
            emarioh_sanitize_payment_log_text((string) ($log['notes'] ?? '')),
        ]);
    }

    return implode('|', array_filter($serializedEntries));
}

function emarioh_log_payment_status(
    PDO $db,
    int $invoiceId,
    int $bookingId,
    ?int $actorUserId,
    string $title,
    ?string $summary = null,
    ?string $metaLabel = null,
    ?string $amountLabel = null,
    ?string $statusClass = null,
    ?string $statusLabel = null,
    ?string $notes = null
): void {
    if ($invoiceId < 1 || $bookingId < 1) {
        return;
    }

    $db->prepare('
        INSERT INTO payment_logs (
            invoice_id,
            booking_id,
            actor_user_id,
            title,
            summary,
            meta_label,
            amount_label,
            status_class,
            status_label,
            notes
        ) VALUES (
            :invoice_id,
            :booking_id,
            :actor_user_id,
            :title,
            :summary,
            :meta_label,
            :amount_label,
            :status_class,
            :status_label,
            :notes
        )
    ')->execute([
        ':invoice_id' => $invoiceId,
        ':booking_id' => $bookingId,
        ':actor_user_id' => $actorUserId,
        ':title' => $title,
        ':summary' => emarioh_trim_or_null($summary),
        ':meta_label' => emarioh_trim_or_null($metaLabel),
        ':amount_label' => emarioh_trim_or_null($amountLabel),
        ':status_class' => emarioh_trim_or_null($statusClass),
        ':status_label' => emarioh_trim_or_null($statusLabel),
        ':notes' => emarioh_trim_or_null($notes),
    ]);
}

function emarioh_ensure_payment_invoice_for_booking(
    PDO $db,
    array $booking,
    ?array $package = null,
    ?int $createdByUserId = null
): ?array {
    $bookingId = (int) ($booking['id'] ?? 0);
    $bookingStatus = strtolower(trim((string) ($booking['status'] ?? '')));

    if ($bookingId < 1 || !in_array($bookingStatus, ['approved', 'completed'], true)) {
        return null;
    }

    $package = $package ?? emarioh_find_service_package_for_booking($db, $booking);
    $paymentSettings = emarioh_fetch_payment_settings($db);
    $existingInvoice = emarioh_find_payment_invoice_by_booking($db, $bookingId);
    $paymentPlan = emarioh_resolve_booking_payment_plan(
        $booking,
        $package,
        $existingInvoice,
        (bool) ($paymentSettings['allow_full_payment'] ?? true)
    );
    $amountDueValue = max(
        $paymentPlan['full_amount_value'] ?? 0,
        max(0, (float) ($existingInvoice['amount_paid'] ?? 0))
    );

    if ($amountDueValue <= 0) {
        return null;
    }

    $invoiceNumber = emarioh_client_invoice_reference((string) ($booking['reference'] ?? ''));
    $packageLabel = trim((string) ($booking['package_label'] ?? ''));
    $eventType = trim((string) ($booking['event_type'] ?? 'Booking'));
    $packageAllowsDownPayment = (bool) ($paymentPlan['package_allows_down_payment'] ?? false);
    $existingAmountPaidValue = max(0, (float) ($existingInvoice['amount_paid'] ?? 0));
    $updatedBalanceValue = max(0, $amountDueValue - $existingAmountPaidValue);
    $invoiceType = $existingAmountPaidValue > 0.00001 && $updatedBalanceValue > 0.00001
        ? 'final_balance'
        : ($packageAllowsDownPayment ? 'down_payment' : 'full_payment');
    $invoiceTitle = trim(($packageLabel !== '' ? $packageLabel : $eventType) . ' Payment');
    $invoiceDescription = $packageAllowsDownPayment
        ? 'Booking payment via PayMongo QRPh. Down payment and full payment options may be available.'
        : 'Booking payment via PayMongo QRPh.';
    $dueDate = emarioh_resolve_payment_due_date(
        (string) ($booking['event_date'] ?? ''),
        (string) ($paymentSettings['balance_due_rule'] ?? '3 days before event')
    );

    if ($existingInvoice === null) {
        $db->prepare('
            INSERT INTO payment_invoices (
                invoice_number,
                booking_id,
                user_id,
                invoice_type,
                title,
                description,
                payment_method,
                gateway_provider,
                currency_code,
                amount_due,
                amount_paid,
                balance_due,
                due_date,
                status,
                stage_label,
                note_text,
                created_by_user_id
            ) VALUES (
                :invoice_number,
                :booking_id,
                :user_id,
                :invoice_type,
                :title,
                :description,
                :payment_method,
                :gateway_provider,
                :currency_code,
                :amount_due,
                :amount_paid,
                :balance_due,
                :due_date,
                :status,
                :stage_label,
                :note_text,
                :created_by_user_id
            )
        ')->execute([
            ':invoice_number' => $invoiceNumber,
            ':booking_id' => $bookingId,
            ':user_id' => (int) ($booking['user_id'] ?? 0) > 0 ? (int) $booking['user_id'] : null,
            ':invoice_type' => $invoiceType,
            ':title' => $invoiceTitle,
            ':description' => $invoiceDescription,
            ':payment_method' => 'PayMongo QRPh',
            ':gateway_provider' => 'PayMongo',
            ':currency_code' => 'PHP',
            ':amount_due' => number_format($amountDueValue, 2, '.', ''),
            ':amount_paid' => '0.00',
            ':balance_due' => number_format($amountDueValue, 2, '.', ''),
            ':due_date' => $dueDate,
            ':status' => 'pending',
            ':stage_label' => 'Awaiting PayMongo QRPh payment',
            ':note_text' => emarioh_trim_or_null((string) ($paymentSettings['instruction_text'] ?? '')),
            ':created_by_user_id' => $createdByUserId,
        ]);

        $invoiceId = (int) $db->lastInsertId();
        emarioh_log_payment_status(
            $db,
            $invoiceId,
            $bookingId,
            $createdByUserId,
            'Invoice Created',
            'Billing invoice opened for this approved booking.',
            $invoiceNumber . ' | ' . $invoiceTitle,
            emarioh_format_money_amount($amountDueValue),
            'pending',
            'Open',
            'Client can now continue to PayMongo QRPh checkout.'
        );

        return emarioh_find_payment_invoice_by_booking($db, $bookingId);
    }

    $existingStatus = trim((string) ($existingInvoice['status'] ?? 'pending'));
    $nextStatus = $updatedBalanceValue <= 0.00001 && $existingAmountPaidValue > 0.00001
        ? 'approved'
        : ($existingStatus === 'cancelled'
            ? 'cancelled'
            : ($existingAmountPaidValue > 0.00001 ? 'review' : 'pending'));
    $stageLabel = $nextStatus === 'approved'
        ? 'Paid via PayMongo QRPh'
        : ($nextStatus === 'cancelled'
            ? 'Invoice Cancelled'
            : ($existingAmountPaidValue > 0.00001
                ? 'Partially Paid via PayMongo QRPh'
                : 'Awaiting PayMongo QRPh payment'));

    $db->prepare('
        UPDATE payment_invoices
        SET invoice_number = :invoice_number,
            user_id = :user_id,
            invoice_type = :invoice_type,
            title = :title,
            description = :description,
            payment_method = :payment_method,
            gateway_provider = :gateway_provider,
            amount_due = :amount_due,
            balance_due = :balance_due,
            due_date = :due_date,
            status = :status,
            stage_label = :stage_label,
            note_text = :note_text
        WHERE id = :id
    ')->execute([
        ':invoice_number' => $invoiceNumber,
        ':user_id' => (int) ($booking['user_id'] ?? 0) > 0 ? (int) $booking['user_id'] : null,
        ':invoice_type' => $invoiceType,
        ':title' => $invoiceTitle,
        ':description' => $invoiceDescription,
        ':payment_method' => 'PayMongo QRPh',
        ':gateway_provider' => 'PayMongo',
        ':amount_due' => number_format($amountDueValue, 2, '.', ''),
        ':balance_due' => number_format($updatedBalanceValue, 2, '.', ''),
        ':due_date' => $dueDate,
        ':status' => $nextStatus,
        ':stage_label' => $stageLabel,
        ':note_text' => emarioh_trim_or_null((string) ($paymentSettings['instruction_text'] ?? '')),
        ':id' => (int) $existingInvoice['id'],
    ]);

    return emarioh_find_payment_invoice_by_booking($db, $bookingId);
}

function emarioh_cancel_payment_invoice_for_booking(PDO $db, int $bookingId, ?int $actorUserId = null): void
{
    $invoice = emarioh_find_payment_invoice_by_booking($db, $bookingId);

    if ($invoice === null || trim((string) ($invoice['status'] ?? '')) === 'cancelled') {
        return;
    }

    $remainingBalanceValue = max(0, (float) ($invoice['amount_due'] ?? 0) - (float) ($invoice['amount_paid'] ?? 0));

    $db->prepare('
        UPDATE payment_invoices
        SET status = :status,
            balance_due = :balance_due,
            stage_label = :stage_label,
            note_text = :note_text
        WHERE id = :id
    ')->execute([
        ':status' => 'cancelled',
        ':balance_due' => number_format($remainingBalanceValue, 2, '.', ''),
        ':stage_label' => 'Invoice Cancelled',
        ':note_text' => 'Booking was cancelled before payment was completed.',
        ':id' => (int) $invoice['id'],
    ]);

    emarioh_log_payment_status(
        $db,
        (int) $invoice['id'],
        (int) $invoice['booking_id'],
        $actorUserId,
        'Invoice Cancelled',
        'Billing invoice closed because the booking was cancelled.',
        (string) ($invoice['invoice_number'] ?? ''),
        emarioh_format_money_amount((float) ($invoice['balance_due'] ?? 0)),
        'inactive',
        'Cancelled',
        'No further PayMongo payment is expected for this booking.'
    );
}

function emarioh_mark_payment_invoice_checkout_ready(
    PDO $db,
    array $invoice,
    string $checkoutSessionId,
    ?string $checkoutUrl = null,
    ?string $checkoutReference = null,
    ?string $checkoutStatus = null,
    ?float $checkoutAmountValue = null
): ?array {
    $invoiceId = (int) ($invoice['id'] ?? 0);

    if ($invoiceId < 1) {
        return null;
    }

    $db->prepare('
        UPDATE payment_invoices
        SET gateway_checkout_session_id = :gateway_checkout_session_id,
            gateway_checkout_reference = :gateway_checkout_reference,
            gateway_checkout_url = :gateway_checkout_url,
            gateway_checkout_status = :gateway_checkout_status,
            gateway_payment_id = NULL,
            gateway_payment_intent_id = NULL,
            gateway_paid_at = NULL,
            stage_label = :stage_label
        WHERE id = :id
    ')->execute([
        ':gateway_checkout_session_id' => emarioh_trim_or_null($checkoutSessionId),
        ':gateway_checkout_reference' => emarioh_trim_or_null($checkoutReference),
        ':gateway_checkout_url' => emarioh_trim_or_null($checkoutUrl),
        ':gateway_checkout_status' => emarioh_trim_or_null($checkoutStatus ?: 'active'),
        ':stage_label' => 'PayMongo QRPh checkout ready',
        ':id' => $invoiceId,
    ]);

    emarioh_log_payment_status(
        $db,
        $invoiceId,
        (int) ($invoice['booking_id'] ?? 0),
        null,
        'Checkout Created',
        'A PayMongo QRPh checkout session was prepared for the client.',
        trim(($invoice['invoice_number'] ?? '') . ' | ' . ($checkoutReference ?? 'PayMongo')),
        emarioh_format_money_amount((float) ($checkoutAmountValue ?? ($invoice['balance_due'] ?? 0))),
        'pending',
        'Awaiting Payment',
        'Client can now open the PayMongo QRPh checkout to settle this invoice.'
    );

    return emarioh_find_payment_invoice_by_booking($db, (int) ($invoice['booking_id'] ?? 0));
}

function emarioh_paymongo_checkout_session_is_missing(Throwable $throwable): bool
{
    $statusCode = (int) $throwable->getCode();

    if ($statusCode === 404) {
        return true;
    }

    $message = strtolower(trim($throwable->getMessage()));

    if ($message === '') {
        return false;
    }

    return str_contains($message, '404')
        || str_contains($message, 'not found')
        || str_contains($message, 'no such');
}

function emarioh_clear_payment_invoice_checkout_state(PDO $db, array $invoice): ?array
{
    $invoiceId = (int) ($invoice['id'] ?? 0);
    $bookingId = (int) ($invoice['booking_id'] ?? 0);

    if ($invoiceId < 1 || $bookingId < 1) {
        return null;
    }

    $status = strtolower(trim((string) ($invoice['status'] ?? 'pending')));
    $amountPaidValue = max(0, (float) ($invoice['amount_paid'] ?? 0));
    $balanceDueValue = max(0, (float) ($invoice['balance_due'] ?? 0));
    $stageLabel = $status === 'approved' && $balanceDueValue <= 0.00001
        ? 'Paid via PayMongo QRPh'
        : ($amountPaidValue > 0 && $balanceDueValue > 0.00001
            ? 'Awaiting remaining balance payment'
            : ($status === 'cancelled'
                ? 'Invoice Closed'
                : 'Awaiting PayMongo QRPh payment'));

    $db->prepare('
        UPDATE payment_invoices
        SET gateway_checkout_session_id = NULL,
            gateway_checkout_reference = NULL,
            gateway_checkout_url = NULL,
            gateway_checkout_status = NULL,
            stage_label = :stage_label
        WHERE id = :id
    ')->execute([
        ':stage_label' => $stageLabel,
        ':id' => $invoiceId,
    ]);

    return emarioh_find_payment_invoice_by_booking($db, $bookingId);
}

function emarioh_paymongo_checkout_status_is_open(?string $status): bool
{
    $normalizedStatus = strtolower(trim((string) $status));

    if ($normalizedStatus === '') {
        return false;
    }

    return in_array($normalizedStatus, [
        'active',
        'open',
        'pending',
        'processing',
        'awaiting_payment',
        'unpaid',
    ], true);
}

function emarioh_payment_invoice_has_remaining_balance(array $invoice): bool
{
    return max(0, (float) ($invoice['amount_paid'] ?? 0)) > 0.00001
        && max(0, (float) ($invoice['balance_due'] ?? 0)) > 0.00001;
}

function emarioh_payment_invoice_has_recorded_payment(array $invoice): bool
{
    return trim((string) ($invoice['gateway_payment_id'] ?? '')) !== ''
        || trim((string) ($invoice['gateway_paid_at'] ?? '')) !== '';
}

function emarioh_payment_invoice_should_skip_checkout_sync(array $invoice): bool
{
    return emarioh_payment_invoice_has_remaining_balance($invoice)
        && emarioh_payment_invoice_has_recorded_payment($invoice);
}

function emarioh_extract_paymongo_payment_summary(array $checkoutSession): array
{
    $sessionData = $checkoutSession['data']['attributes'] ?? [];
    $payments = $sessionData['payments'] ?? [];
    $firstPayment = is_array($payments) && isset($payments[0]) && is_array($payments[0])
        ? $payments[0]
        : [];
    $paymentAttributes = is_array($firstPayment['attributes'] ?? null)
        ? $firstPayment['attributes']
        : [];
    $amountValue = isset($paymentAttributes['amount'])
        ? ((float) $paymentAttributes['amount'] / 100)
        : 0.0;

    return [
        'checkout_session_id' => trim((string) ($checkoutSession['data']['id'] ?? '')),
        'checkout_status' => trim((string) ($sessionData['status'] ?? '')),
        'checkout_reference' => trim((string) ($sessionData['reference_number'] ?? '')),
        'checkout_url' => trim((string) ($sessionData['checkout_url'] ?? '')),
        'payment_id' => trim((string) ($firstPayment['id'] ?? '')),
        'payment_intent_id' => trim((string) ($paymentAttributes['payment_intent_id'] ?? '')),
        'payment_status' => trim((string) ($paymentAttributes['status'] ?? '')),
        'amount_paid' => $amountValue > 0 ? $amountValue : 0.0,
        'metadata' => is_array($sessionData['metadata'] ?? null) ? $sessionData['metadata'] : [],
    ];
}

function emarioh_mark_payment_invoice_paid(PDO $db, array $invoice, array $paymentSummary): ?array
{
    $invoiceId = (int) ($invoice['id'] ?? 0);
    $bookingId = (int) ($invoice['booking_id'] ?? 0);

    if ($invoiceId < 1 || $bookingId < 1) {
        return null;
    }

    $amountDueValue = max(0, (float) ($invoice['amount_due'] ?? 0));
    $existingAmountPaidValue = max(0, (float) ($invoice['amount_paid'] ?? 0));
    $newPaymentAmountValue = max(0, (float) ($paymentSummary['amount_paid'] ?? 0));

    if ($newPaymentAmountValue <= 0) {
        $newPaymentAmountValue = max(0, (float) ($invoice['balance_due'] ?? 0));
    }

    if ($newPaymentAmountValue <= 0) {
        $newPaymentAmountValue = max(0, $amountDueValue - $existingAmountPaidValue);
    }

    $amountPaidValue = min($amountDueValue, $existingAmountPaidValue + $newPaymentAmountValue);
    $remainingBalanceValue = max(0, $amountDueValue - $amountPaidValue);
    $paidAt = date('Y-m-d H:i:s');
    $paymentId = trim((string) ($paymentSummary['payment_id'] ?? ''));
    $alreadyRecorded = $paymentId !== ''
        && trim((string) ($invoice['gateway_payment_id'] ?? '')) === $paymentId
        && trim((string) ($invoice['gateway_paid_at'] ?? '')) !== '';

    if ($alreadyRecorded) {
        return emarioh_find_payment_invoice_by_booking($db, $bookingId) ?? $invoice;
    }

    $db->prepare('
        UPDATE payment_invoices
        SET amount_paid = :amount_paid,
            balance_due = :balance_due,
            status = :status,
            stage_label = :stage_label,
            last_payment_at = :last_payment_at,
            gateway_checkout_session_id = :gateway_checkout_session_id,
            gateway_checkout_reference = :gateway_checkout_reference,
            gateway_checkout_url = :gateway_checkout_url,
            gateway_checkout_status = :gateway_checkout_status,
            gateway_payment_id = :gateway_payment_id,
            gateway_payment_intent_id = :gateway_payment_intent_id,
            gateway_paid_at = :gateway_paid_at,
            note_text = :note_text
        WHERE id = :id
    ')->execute([
        ':amount_paid' => number_format($amountPaidValue, 2, '.', ''),
        ':balance_due' => number_format($remainingBalanceValue, 2, '.', ''),
        ':status' => $remainingBalanceValue <= 0.00001 ? 'approved' : 'review',
        ':stage_label' => $remainingBalanceValue <= 0.00001 ? 'Paid via PayMongo QRPh' : 'Partially Paid via PayMongo QRPh',
        ':last_payment_at' => $paidAt,
        ':gateway_checkout_session_id' => emarioh_trim_or_null((string) ($paymentSummary['checkout_session_id'] ?? '')),
        ':gateway_checkout_reference' => emarioh_trim_or_null((string) ($paymentSummary['checkout_reference'] ?? '')),
        ':gateway_checkout_url' => emarioh_trim_or_null((string) ($paymentSummary['checkout_url'] ?? '')),
        ':gateway_checkout_status' => emarioh_trim_or_null((string) ($paymentSummary['checkout_status'] ?? 'paid')),
        ':gateway_payment_id' => emarioh_trim_or_null($paymentId),
        ':gateway_payment_intent_id' => emarioh_trim_or_null((string) ($paymentSummary['payment_intent_id'] ?? '')),
        ':gateway_paid_at' => $paidAt,
        ':note_text' => $remainingBalanceValue <= 0.00001
            ? 'Payment confirmed by PayMongo QRPh.'
            : 'Partial payment confirmed by PayMongo QRPh.',
        ':id' => $invoiceId,
    ]);

    emarioh_log_payment_status(
        $db,
        $invoiceId,
        $bookingId,
        null,
        'Payment Received',
        'PayMongo confirmed the client payment for this invoice.',
        trim((string) ($invoice['invoice_number'] ?? '') . ' | ' . ($paymentId !== '' ? $paymentId : 'PayMongo')),
        emarioh_format_money_amount($newPaymentAmountValue),
        $remainingBalanceValue <= 0.00001 ? 'approved' : 'review',
        $remainingBalanceValue <= 0.00001 ? 'Paid' : 'Partially Paid',
        $remainingBalanceValue <= 0.00001
            ? 'Invoice is now fully paid.'
            : 'Payment was posted but a remaining balance is still open.'
    );

    $updatedInvoice = emarioh_find_payment_invoice_by_booking($db, $bookingId);
    $booking = emarioh_find_booking_by_id($db, $bookingId);

    if ($updatedInvoice !== null && max(0, (float) ($updatedInvoice['amount_paid'] ?? 0)) > 0) {
        emarioh_upsert_system_payment_receipt(
            $db,
            $updatedInvoice,
            $booking,
            $paymentSummary
        );
    }

    if ($booking !== null) {
        try {
            emarioh_send_booking_sms_template(
                $db,
                $booking,
                'payment_verified',
                [
                    'trigger_label' => 'Payment verified',
                    'source_label' => 'Payment Management',
                ]
            );
        } catch (Throwable $throwable) {
            // Keep payment sync successful even when the SMS gateway is unavailable.
        }
    }

    return $updatedInvoice;
}

function emarioh_sync_paymongo_invoice_status(PDO $db, array $invoice): ?array
{
    $checkoutSessionId = trim((string) ($invoice['gateway_checkout_session_id'] ?? ''));

    if ($checkoutSessionId === '' || !emarioh_paymongo_has_secret_key()) {
        return $invoice;
    }

    try {
        $checkoutSession = emarioh_paymongo_request('GET', 'checkout_sessions/' . rawurlencode($checkoutSessionId));
    } catch (RuntimeException $exception) {
        // Some older or already-consumed sessions can disappear upstream; clear the stale reference
        // so the client can continue to open a fresh checkout for any remaining balance.
        if (emarioh_paymongo_checkout_session_is_missing($exception)) {
            return emarioh_clear_payment_invoice_checkout_state($db, $invoice) ?? $invoice;
        }

        throw $exception;
    }

    $paymentSummary = emarioh_extract_paymongo_payment_summary($checkoutSession);

    if (trim((string) ($paymentSummary['payment_status'] ?? '')) === 'paid') {
        return emarioh_mark_payment_invoice_paid($db, $invoice, $paymentSummary);
    }

    if (trim((string) ($paymentSummary['checkout_status'] ?? '')) !== '') {
        $db->prepare('
            UPDATE payment_invoices
            SET gateway_checkout_status = :gateway_checkout_status,
                stage_label = :stage_label
            WHERE id = :id
        ')->execute([
            ':gateway_checkout_status' => (string) $paymentSummary['checkout_status'],
            ':stage_label' => 'PayMongo QRPh checkout ready',
            ':id' => (int) $invoice['id'],
        ]);
    }

    return emarioh_find_payment_invoice_by_booking($db, (int) ($invoice['booking_id'] ?? 0));
}

function emarioh_build_client_portal_billing_details(PDO $db, array $booking, ?array $package = null): ?array
{
    $status = strtolower(trim((string) ($booking['status'] ?? 'pending_review')));

    if (!in_array($status, ['approved', 'completed'], true)) {
        return null;
    }

    $package = $package ?? emarioh_find_service_package_for_booking($db, $booking);
    $invoice = emarioh_find_payment_invoice_by_booking($db, (int) ($booking['id'] ?? 0));

    if ($invoice === null) {
        $invoice = emarioh_ensure_payment_invoice_for_booking(
            $db,
            $booking,
            $package,
            (int) ($booking['reviewed_by_user_id'] ?? 0) > 0 ? (int) $booking['reviewed_by_user_id'] : null
        );
    }

    if ($invoice === null) {
        return null;
    }

    $paymentSettings = emarioh_fetch_payment_settings($db);
    $paymentPlan = emarioh_resolve_booking_payment_plan(
        $booking,
        $package,
        $invoice,
        (bool) ($paymentSettings['allow_full_payment'] ?? true)
    );
    $invoiceStatus = strtolower(trim((string) ($invoice['status'] ?? 'pending')));
    $amountDueValue = max(0, (float) ($invoice['amount_due'] ?? 0));
    $amountPaidValue = max(0, (float) ($invoice['amount_paid'] ?? 0));
    $balanceDueValue = max(0, (float) ($invoice['balance_due'] ?? 0));
    $amountToPayNowValue = max(0, (float) ($paymentPlan['charge_amount_value'] ?? $balanceDueValue));
    $receipt = $amountPaidValue > 0
        ? emarioh_upsert_system_payment_receipt($db, $invoice, $booking)
        : null;
    $receiptHref = $receipt !== null
        ? 'client-payment-receipt.php?invoice=' . rawurlencode((string) ($invoice['invoice_number'] ?? ''))
        : '';
    $receiptDownloadHref = $receipt !== null
        ? $receiptHref . '&print=1'
        : '';

    $statusMeta = match ($invoiceStatus) {
        'approved' => ['label' => 'Paid', 'class' => 'approved', 'stage' => 'Payment Confirmed'],
        'review' => ['label' => 'Partially Paid', 'class' => 'review', 'stage' => 'Payment Review'],
        'rejected' => ['label' => 'Payment Failed', 'class' => 'rejected', 'stage' => 'Checkout Needs Attention'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'cancelled', 'stage' => 'Invoice Closed'],
        default => ['label' => 'Open', 'class' => 'pending', 'stage' => 'Awaiting Payment'],
    };

    return [
        'invoiceNumber' => (string) ($invoice['invoice_number'] ?? emarioh_client_invoice_reference((string) ($booking['reference'] ?? ''))),
        'paymentMethod' => 'PayMongo QRPh',
        'amountDue' => emarioh_format_money_amount($amountToPayNowValue),
        'amountDueValue' => $amountToPayNowValue,
        'amountToPayNow' => emarioh_format_money_amount($amountToPayNowValue),
        'amountToPayNowValue' => $amountToPayNowValue,
        'totalAmount' => emarioh_format_money_amount($amountDueValue),
        'totalAmountValue' => $amountDueValue,
        'totalPaid' => emarioh_format_money_amount($amountPaidValue),
        'totalPaidValue' => $amountPaidValue,
        'pendingBalance' => emarioh_format_money_amount($balanceDueValue),
        'pendingBalanceValue' => $balanceDueValue,
        'downPaymentAmount' => ($paymentPlan['down_payment_amount_value'] ?? 0) > 0
            ? emarioh_format_money_amount((float) $paymentPlan['down_payment_amount_value'])
            : '',
        'downPaymentAmountValue' => (float) ($paymentPlan['down_payment_amount_value'] ?? 0),
        'selectedPaymentOption' => (string) ($paymentPlan['selected_option'] ?? 'full_payment'),
        'selectedPaymentOptionLabel' => (string) ($paymentPlan['selected_option_label'] ?? 'Full Payment'),
        'availablePaymentOptions' => $paymentPlan['available_options'] ?? [],
        'paymentChoiceHelpText' => (string) ($paymentPlan['help_text'] ?? ''),
        'allowFullPayment' => (bool) ($paymentPlan['allow_full_payment'] ?? true),
        'packageAllowsDownPayment' => (bool) ($paymentPlan['package_allows_down_payment'] ?? false),
        'description' => trim((string) ($invoice['title'] ?? ($booking['package_label'] ?? 'Booking Payment'))),
        'statusText' => $statusMeta['label'],
        'statusPillClass' => $statusMeta['class'],
        'paymentStage' => (string) ($invoice['stage_label'] ?? $statusMeta['stage']),
        'dueDate' => (string) ($invoice['due_date'] ?? ''),
        'invoiceHref' => 'client-billing.php',
        'receiptHref' => $receiptHref,
        'receiptDownloadHref' => $receiptDownloadHref,
        'receiptReference' => (string) ($receipt['receipt_reference'] ?? ''),
        'receiptStatus' => (string) ($receipt['status'] ?? ''),
        'invoiceCreatedAt' => (string) ($invoice['created_at'] ?? ''),
        'lastPayment' => (string) ($invoice['last_payment_at'] ?? ''),
        'paymentNote' => trim((string) ($invoice['note_text'] ?? '')),
        'paymentProvider' => 'PayMongo',
        'gatewayCheckoutSessionId' => (string) ($invoice['gateway_checkout_session_id'] ?? ''),
        'gatewayCheckoutReference' => (string) ($invoice['gateway_checkout_reference'] ?? ''),
        'gatewayCheckoutStatus' => (string) ($invoice['gateway_checkout_status'] ?? ''),
        'gatewayPaymentId' => (string) ($invoice['gateway_payment_id'] ?? ''),
        'gatewayPaidAt' => (string) ($invoice['gateway_paid_at'] ?? ''),
        'canPayOnline' => $invoiceStatus !== 'approved' && $invoiceStatus !== 'cancelled',
        'isPaymongoEnabled' => emarioh_paymongo_is_ready(),
        'paymentLog' => emarioh_serialize_payment_logs(emarioh_fetch_payment_logs_for_invoice($db, (int) ($invoice['id'] ?? 0))),
    ];
}

function emarioh_create_paymongo_checkout_session(PDO $db, array $invoice, array $booking, array $paymentPlan = []): array
{
    if (!emarioh_paymongo_is_ready()) {
        throw new RuntimeException('PayMongo QRPh checkout is not configured yet.');
    }

    $invoiceId = (int) ($invoice['id'] ?? 0);
    $bookingId = (int) ($booking['id'] ?? 0);
    $remainingBalanceValue = max(0, (float) ($invoice['balance_due'] ?? 0));

    if ($remainingBalanceValue <= 0) {
        $remainingBalanceValue = max(0, (float) ($invoice['amount_due'] ?? 0) - (float) ($invoice['amount_paid'] ?? 0));
    }

    $amountValue = max(0, (float) ($paymentPlan['charge_amount_value'] ?? 0));

    if ($amountValue <= 0) {
        $amountValue = $remainingBalanceValue;
    }

    if ($remainingBalanceValue > 0) {
        $amountValue = min($amountValue, $remainingBalanceValue);
    }

    if ($invoiceId < 1 || $bookingId < 1 || $amountValue <= 0) {
        throw new RuntimeException('No payable balance is available for this invoice.');
    }

    $checkoutTitle = trim((string) ($paymentPlan['selected_option_label'] ?? 'Payment'));
    $lineItemName = trim((string) ($invoice['title'] ?? 'Booking Payment'));
    $lineItemDescription = trim((string) ($invoice['description'] ?? 'Booking payment via PayMongo QRPh.'));

    if ($checkoutTitle !== '') {
        $lineItemName = trim($lineItemName . ' - ' . $checkoutTitle);
    }

    $payload = [
        'data' => [
            'attributes' => [
                'line_items' => [[
                    'currency' => 'PHP',
                    'amount' => (int) round($amountValue * 100),
                    'name' => $lineItemName,
                    'quantity' => 1,
                    'description' => $lineItemDescription,
                ]],
                'payment_method_types' => ['qrph'],
                'success_url' => emarioh_app_url('client-billing.php?payment=paymongo_return&invoice=' . rawurlencode((string) ($invoice['invoice_number'] ?? ''))),
                'cancel_url' => emarioh_app_url('client-billing.php?payment=cancelled&invoice=' . rawurlencode((string) ($invoice['invoice_number'] ?? ''))),
                'description' => $lineItemDescription,
                'reference_number' => trim((string) ($invoice['invoice_number'] ?? '')),
                'send_email_receipt' => false,
                'show_description' => true,
                'show_line_items' => true,
                'metadata' => [
                    'invoice_number' => trim((string) ($invoice['invoice_number'] ?? '')),
                    'booking_id' => (string) $bookingId,
                    'booking_reference' => trim((string) ($booking['reference'] ?? '')),
                    'user_id' => trim((string) ($booking['user_id'] ?? '')),
                    'payment_option' => trim((string) ($paymentPlan['selected_option'] ?? 'full_payment')),
                    'payment_option_label' => $checkoutTitle,
                ],
            ],
        ],
    ];
    $response = emarioh_paymongo_request('POST', 'checkout_sessions', $payload);
    $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
    $responseAttributes = is_array($responseData['attributes'] ?? null) ? $responseData['attributes'] : [];
    $checkoutSessionId = trim((string) ($responseData['id'] ?? ''));
    $checkoutUrl = trim((string) ($responseAttributes['checkout_url'] ?? ''));

    if ($checkoutSessionId === '' || $checkoutUrl === '') {
        throw new RuntimeException('PayMongo checkout session was created without a usable checkout URL.');
    }

    emarioh_mark_payment_invoice_checkout_ready(
        $db,
        $invoice,
        $checkoutSessionId,
        $checkoutUrl,
        trim((string) ($responseAttributes['reference_number'] ?? '')),
        trim((string) ($responseAttributes['status'] ?? 'active')),
        $amountValue
    );

    return [
        'checkout_session_id' => $checkoutSessionId,
        'checkout_url' => $checkoutUrl,
        'reference_number' => trim((string) ($responseAttributes['reference_number'] ?? '')),
        'status' => trim((string) ($responseAttributes['status'] ?? 'active')),
    ];
}

function emarioh_parse_paymongo_signature_header(string $headerValue): array
{
    $parsedHeader = [];

    foreach (explode(',', $headerValue) as $segment) {
        $parts = explode('=', trim($segment), 2);

        if (count($parts) !== 2) {
            continue;
        }

        $parsedHeader[strtolower(trim((string) $parts[0]))] = trim((string) $parts[1]);
    }

    return $parsedHeader;
}

function emarioh_verify_paymongo_signature(string $rawBody, string $headerValue, bool $isLiveMode): bool
{
    $config = emarioh_paymongo_config();
    $webhookSecret = trim((string) ($config['webhook_secret'] ?? ''));

    if ($webhookSecret === '') {
        return true;
    }

    $parsedHeader = emarioh_parse_paymongo_signature_header($headerValue);
    $timestamp = trim((string) ($parsedHeader['t'] ?? ''));
    $providedSignature = trim((string) ($isLiveMode ? ($parsedHeader['li'] ?? '') : ($parsedHeader['te'] ?? '')));

    if ($timestamp === '' || $providedSignature === '') {
        return false;
    }

    $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $rawBody, $webhookSecret);
    return hash_equals($expectedSignature, $providedSignature);
}

function emarioh_map_client_portal_booking_request(array $booking, ?array $package = null): array
{
    $bookingId = (int) ($booking['id'] ?? 0);
    $reference = trim((string) ($booking['reference'] ?? ''));
    $status = trim((string) ($booking['status'] ?? 'pending_review'));
    $packageLabel = trim((string) ($booking['package_label'] ?? ''));
    $eventType = trim((string) ($booking['event_type'] ?? 'Booking'));
    $packageSelectionValue = (string) ($booking['package_selection_value'] ?? '');
    $packageBaseValue = (string) ($package['package_code'] ?? '');

    if ($packageBaseValue === '' && $packageSelectionValue !== '') {
        $packageBaseValue = trim((string) explode('::', $packageSelectionValue, 2)[0]);
    }

    $packageDownPaymentAmount = emarioh_resolve_booking_down_payment_amount_label($booking, $package);
    $packageAllowsDownPayment = $packageDownPaymentAmount !== '';
    $descriptionBase = $packageLabel !== '' ? $packageLabel : ($eventType !== '' ? $eventType : 'Booking');
    $amountDue = emarioh_resolve_client_portal_amount_due($booking, $package);
    $bookingRequest = [
        'id' => $bookingId,
        'reference' => $reference,
        'status' => $status,
        'submittedAt' => (string) ($booking['submitted_at'] ?? ''),
        'eventType' => $eventType,
        'eventDate' => (string) ($booking['event_date'] ?? ''),
        'eventTime' => substr((string) ($booking['event_time'] ?? ''), 0, 5),
        'guestCount' => (string) (int) ($booking['guest_count'] ?? 0),
        'venueOption' => (string) ($booking['venue_option'] ?? 'own'),
        'venue' => (string) ($booking['venue_name'] ?? ''),
        'packageCategoryValue' => (string) ($booking['package_category_value'] ?? ''),
        'packageValue' => $packageSelectionValue,
        'packageBaseValue' => $packageBaseValue,
        'packageTierLabel' => (string) ($booking['package_tier_label'] ?? ''),
        'packageTierPrice' => (string) ($booking['package_tier_price'] ?? ''),
        'packageLabel' => $packageLabel,
        'primaryContact' => (string) ($booking['primary_contact'] ?? ''),
        'primaryMobile' => (string) ($booking['primary_mobile'] ?? ''),
        'alternateContact' => (string) ($booking['alternate_contact'] ?? ''),
        'notes' => (string) ($booking['event_notes'] ?? ''),
        'packageAllowsDownPayment' => $packageAllowsDownPayment,
        'packageDownPaymentAmount' => $packageDownPaymentAmount,
    ];

    if (!in_array($status, ['approved', 'completed'], true)) {
        return [
            'bookingRequest' => $bookingRequest,
            'billingDetails' => null,
        ];
    }

    $descriptionSuffix = $packageAllowsDownPayment ? 'Down Payment' : 'Payment';
    $billingDetails = [
        'invoiceNumber' => emarioh_client_invoice_reference($reference),
        'paymentMethod' => 'PayMongo QRPh',
        'amountDue' => $amountDue ?? '',
        'pendingBalance' => $amountDue ?? '',
        'description' => trim($descriptionBase . ' ' . $descriptionSuffix),
        'statusText' => 'Open',
        'statusPillClass' => 'pending',
        'invoiceHref' => '#',
    ];

    return [
        'bookingRequest' => $bookingRequest,
        'billingDetails' => $billingDetails,
    ];
}

function emarioh_select_client_portal_booking(array $bookings): ?array
{
    if ($bookings === []) {
        return null;
    }

    $statusPriorityGroups = [
        ['approved', 'completed'],
        ['pending_review'],
        ['cancelled'],
        ['rejected'],
    ];

    foreach ($statusPriorityGroups as $statusGroup) {
        foreach ($bookings as $booking) {
            if (in_array((string) ($booking['status'] ?? ''), $statusGroup, true)) {
                return $booking;
            }
        }
    }

    return $bookings[0] ?? null;
}

function emarioh_fetch_client_portal_state(PDO $db, int $userId, ?string $clientName = null): array
{
    $portalState = [
        'clientName' => trim((string) $clientName),
        'bookingRequest' => null,
        'billingDetails' => null,
    ];

    if ($userId < 1) {
        return $portalState;
    }

    $bookings = emarioh_fetch_booking_requests($db, [
        'user_id' => $userId,
        'order_by' => 'submitted_desc',
    ]);
    $selectedBooking = emarioh_select_client_portal_booking($bookings);

    if ($selectedBooking === null) {
        return $portalState;
    }

    $package = emarioh_find_service_package_for_booking($db, $selectedBooking);
    $mappedState = emarioh_map_client_portal_booking_request($selectedBooking, $package);

    $portalState['bookingRequest'] = $mappedState['bookingRequest'] ?? null;
    $portalState['billingDetails'] = emarioh_build_client_portal_billing_details($db, $selectedBooking, $package);

    if ($portalState['clientName'] === '') {
        $portalState['clientName'] = trim((string) ($selectedBooking['primary_contact'] ?? ''));
    }

    return $portalState;
}

function emarioh_fetch_booking_requests(PDO $db, array $options = []): array
{
    $sql = '
        SELECT
            br.*,
            reviewer.full_name AS reviewer_name
        FROM booking_requests br
        LEFT JOIN users reviewer
            ON reviewer.id = br.reviewed_by_user_id
    ';
    $where = [];
    $params = [];

    if (isset($options['booking_id'])) {
        $where[] = 'br.id = :booking_id';
        $params[':booking_id'] = (int) $options['booking_id'];
    }

    if (isset($options['user_id'])) {
        $where[] = 'br.user_id = :user_id';
        $params[':user_id'] = (int) $options['user_id'];
    }

    if (!empty($options['statuses']) && is_array($options['statuses'])) {
        $statusPlaceholders = [];

        foreach (array_values($options['statuses']) as $index => $status) {
            $placeholder = ':status_' . $index;
            $statusPlaceholders[] = $placeholder;
            $params[$placeholder] = (string) $status;
        }

        if ($statusPlaceholders !== []) {
            $where[] = 'br.status IN (' . implode(', ', $statusPlaceholders) . ')';
        }
    }

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $orderBy = (string) ($options['order_by'] ?? 'submitted_desc');
    $sql .= match ($orderBy) {
        'event_date_asc' => ' ORDER BY br.event_date ASC, br.event_time ASC, br.id ASC',
        'event_date_desc' => ' ORDER BY br.event_date DESC, br.event_time DESC, br.id DESC',
        default => ' ORDER BY br.submitted_at DESC, br.id DESC',
    };

    $limit = isset($options['limit']) ? (int) $options['limit'] : 0;

    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }

    $statement = $db->prepare($sql);
    $statement->execute($params);

    $rows = $statement->fetchAll();
    return is_array($rows) ? $rows : [];
}

function emarioh_find_booking_by_id(PDO $db, int $bookingId): ?array
{
    $bookings = emarioh_fetch_booking_requests($db, [
        'booking_id' => $bookingId,
        'limit' => 1,
    ]);

    return $bookings[0] ?? null;
}

function emarioh_fetch_booked_event_dates(PDO $db): array
{
    $statement = $db->query("
        SELECT DISTINCT DATE_FORMAT(event_date, '%Y-%m-%d') AS event_date
        FROM booking_requests
        WHERE status IN ('approved', 'completed')
        ORDER BY event_date ASC
    ");

    $dates = [];

    foreach ($statement->fetchAll() as $row) {
        $eventDate = trim((string) ($row['event_date'] ?? ''));

        if ($eventDate !== '') {
            $dates[] = $eventDate;
        }
    }

    return $dates;
}

function emarioh_booking_date_has_conflict(PDO $db, string $eventDate, ?int $excludeBookingId = null): bool
{
    $sql = "
        SELECT 1
        FROM booking_requests
        WHERE event_date = :event_date
          AND status IN ('approved', 'completed')
    ";
    $params = [
        ':event_date' => $eventDate,
    ];

    if ($excludeBookingId !== null && $excludeBookingId > 0) {
        $sql .= ' AND id <> :exclude_booking_id';
        $params[':exclude_booking_id'] = $excludeBookingId;
    }

    $sql .= ' LIMIT 1';

    $statement = $db->prepare($sql);
    $statement->execute($params);

    return (bool) $statement->fetchColumn();
}

function emarioh_log_booking_status(
    PDO $db,
    int $bookingId,
    ?int $changedByUserId,
    ?string $fromStatus,
    string $toStatus,
    string $title,
    ?string $summary = null,
    ?string $notes = null
): void {
    $db->prepare('
        INSERT INTO booking_status_logs (
            booking_id,
            changed_by_user_id,
            from_status,
            to_status,
            title,
            summary,
            notes
        ) VALUES (
            :booking_id,
            :changed_by_user_id,
            :from_status,
            :to_status,
            :title,
            :summary,
            :notes
        )
    ')->execute([
        ':booking_id' => $bookingId,
        ':changed_by_user_id' => $changedByUserId,
        ':from_status' => emarioh_trim_or_null($fromStatus),
        ':to_status' => $toStatus,
        ':title' => $title,
        ':summary' => emarioh_trim_or_null($summary),
        ':notes' => emarioh_trim_or_null($notes),
    ]);
}
