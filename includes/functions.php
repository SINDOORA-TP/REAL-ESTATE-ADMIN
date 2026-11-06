<?php
/**
 * Global Helper Functions & Security Utilities
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Escape output to prevent XSS attacks
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Simple session-based Rate Limiter (e.g., max 5 submissions per 60 seconds)
 */
function check_rate_limit(string $actionKey, int $maxAttempts = 5, int $decaySeconds = 60): bool {
    $now = time();
    if (!isset($_SESSION['rate_limits'][$actionKey])) {
        $_SESSION['rate_limits'][$actionKey] = [];
    }

    // Filter out old timestamps
    $_SESSION['rate_limits'][$actionKey] = array_filter(
        $_SESSION['rate_limits'][$actionKey],
        function ($timestamp) use ($now, $decaySeconds) {
            return ($now - $timestamp) < $decaySeconds;
        }
    );

    if (count($_SESSION['rate_limits'][$actionKey]) >= $maxAttempts) {
        return false; // Rate limit exceeded
    }

    $_SESSION['rate_limits'][$actionKey][] = $now;
    return true;
}

/**
 * Sanitize clean text input
 */
function clean_string(?string $input): string {
    return trim(strip_tags($input ?? ''));
}

/**
 * Create URL-friendly slug
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'property-' . time() : $text;
}

/**
 * Format Indian Rupees with Crore / Lakh conventions or fallback
 */
function format_inr(float $amount): string {
    if ($amount >= 10000000) {
        return '₹' . rtrim(rtrim(number_format($amount / 10000000, 2), '0'), '.') . ' Cr';
    } elseif ($amount >= 100000) {
        return '₹' . rtrim(rtrim(number_format($amount / 100000, 2), '0'), '.') . ' Lakh';
    } else {
        return '₹' . number_format($amount, 0);
    }
}

/**
 * Retrieve a site setting from the DB
 */
function get_setting(string $key, string $default = ''): string {
    $db = get_db();
    if (!$db) {
        return $default;
    }

    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch();
        return $row ? (string)$row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Retrieve all settings as an array
 */
function get_all_settings(): array {
    $defaults = [
        'site_name' => 'Magaram Real Estate',
        'tagline' => 'Curating Extraordinary Living Spaces',
        'contact_email' => 'contact@magaramrealestate.com',
        'contact_phone' => '+91 98765 43210',
        'office_address' => 'Magaram Towers, Level 14, Prestige Boulevard, Jubilee Hills, Hyderabad',
        'hero_headline_1' => 'Experience',
        'hero_headline_2' => 'Luxury Living',
        'hero_subtext' => 'Bespoke residences, signature penthouses, and prime commercial estates crafted with architectural precision.',
        'show_stats' => '1',
        'stats_note' => '[Sample Data - Update with Real Figures]',
        'stat_1_val' => '15+',
        'stat_1_lbl' => 'Years of Heritage [Sample]',
        'stat_2_val' => '75+',
        'stat_2_lbl' => 'Bespoke Projects [Sample]',
        'stat_3_val' => '₹350Cr+',
        'stat_3_lbl' => 'Properties Transacted [Sample]',
        'stat_4_val' => '99.2%',
        'stat_4_lbl' => 'Client Satisfaction [Sample]',
        'show_testimonials' => '1',
        'about_preview' => 'At Magaram Real Estate, we bridge discerning patrons with timeless architectural masterworks. Our portfolio is curated exclusively for those who view real estate not simply as assets, but as legacies of distinction.',
        'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3806.6669939527643!2d78.40698717594732!3d17.42777170163351!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bcb96cd3d1f1f1f%3A0x8898124231234567!2sJubilee%20Hills%2C%20Hyderabad!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin'
    ];

    $db = get_db();
    if (!$db) {
        return $defaults;
    }

    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch()) {
            $defaults[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        // Fallback to defaults
    }

    return $defaults;
}

/**
 * Check if Admin is logged in
 */
function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id']);
}

/**
 * Enforce Admin Authentication
 */
function require_admin(): void {
    if (!is_admin_logged_in()) {
        redirect(BASE_URL . '/admin/login.php');
    }
}

/**
 * Flash message helpers
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Safe redirect
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Safe Image Upload Handler
 */
function safe_upload_image(array $file, string $targetDir): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error code: ' . $file['error']];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File exceeds maximum size of 8MB.'];
    }

    // Verify MIME type using FileInfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, and WebP are accepted.'];
    }

    // Verify extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTS)) {
        return ['success' => false, 'error' => 'Invalid file extension.'];
    }

    // Create safe target directory if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Generate non-guessable unique filename
    $newFileName = 'magaram_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file.'];
    }

    return ['success' => true, 'filename' => $newFileName, 'path' => $targetPath];
}

/**
 * Send Email Notification (supports mail() and placeholder for SMTP / PHPMailer)
 */
function send_admin_notification(string $subject, string $body): bool {
    $to = ADMIN_EMAIL;
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . SMTP_FROM_NAME . ' <' . SMTP_USER . '>',
        'Reply-To: ' . ADMIN_EMAIL,
        'X-Mailer: PHP/' . phpversion()
    ];

    // Attempt native mail() - in production with PHPMailer, inject SMTP client here
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Static Fallback Properties (When MySQL isn't connected yet)
 * This ensures the frontend immediately renders the gold-standard royal design even before DB import!
 */
function get_fallback_properties(): array {
    return [
        [
            'id' => 1,
            'title' => 'The Sovereign Penthouse [Sample]',
            'slug' => 'the-sovereign-penthouse',
            'category' => 'Apartment',
            'location' => 'Jubilee Hills, Hyderabad',
            'price' => 145000000,
            'price_display' => '₹14.50 Cr',
            'area_sqft' => 6800,
            'bedrooms' => 5,
            'bathrooms' => 6,
            'description' => 'A magnificent double-height triplex penthouse offering 360-degree skyline vistas. Features private elevator access, an 800 sq.ft wrap-around sky deck with heated infinity plunge pool, imported Calacatta marble, and custom smart automation tailored for royalty.',
            'status' => 'Available',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-1.webp'
        ],
        [
            'id' => 2,
            'title' => 'The Elysian Garden Villa [Sample]',
            'slug' => 'the-elysian-garden-villa',
            'category' => 'Villa',
            'location' => 'Gachibowli Outer Ring, Hyderabad',
            'price' => 220000000,
            'price_display' => '₹22.00 Cr',
            'area_sqft' => 9200,
            'bedrooms' => 6,
            'bathrooms' => 7,
            'description' => 'Set across a private 1.2-acre manicured plot, this contemporary masterpiece combines Mediterranean stone facades with floor-to-ceiling acoustic glass walls, private temperature-controlled wine cellar, serene reflecting ponds, and staff quarters.',
            'status' => 'Available',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-2.webp'
        ],
        [
            'id' => 3,
            'title' => 'Prestige Crown Commercial Tower [Sample]',
            'slug' => 'prestige-crown-commercial-tower',
            'category' => 'Commercial',
            'location' => 'Hitec City, Financial District',
            'price' => 480000000,
            'price_display' => '₹48.00 Cr',
            'area_sqft' => 35000,
            'bedrooms' => 0,
            'bathrooms' => 12,
            'description' => 'LEED Platinum-certified Grade A commercial headquarters. Designed for global enterprise operations with column-free floor plates, high-speed destination-controlled elevators, and state-of-the-art HVAC air-purification.',
            'status' => 'Available',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-3.webp'
        ],
        [
            'id' => 4,
            'title' => 'The Aurum Signature Residence [Sample]',
            'slug' => 'the-aurum-signature-residence',
            'category' => 'Apartment',
            'location' => 'Banjara Hills, Hyderabad',
            'price' => 98000000,
            'price_display' => '₹9.80 Cr',
            'area_sqft' => 4600,
            'bedrooms' => 4,
            'bathrooms' => 4,
            'description' => 'An ultra-exclusive single-floor apartment in a boutique 8-unit development. Boasts soundproofed acoustic suites, private master retreat with walk-in custom Italian wardrobes, and panoramic green valley views.',
            'status' => 'Available',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-4.webp'
        ],
        [
            'id' => 5,
            'title' => 'Serene Palms Estate [Sample]',
            'slug' => 'serene-palms-estate',
            'category' => 'Villa',
            'location' => 'Moinabad Green Valley',
            'price' => 185000000,
            'price_display' => '₹18.50 Cr',
            'area_sqft' => 8400,
            'bedrooms' => 5,
            'bathrooms' => 6,
            'description' => 'An oasis of peace featuring an Olympic-grade private pool, manicured fruit orchards, independent clubhouse pavilion, and state-of-the-art perimeter security.',
            'status' => 'Upcoming',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-5.webp'
        ],
        [
            'id' => 6,
            'title' => 'The Opus Pinnacle Suites [Sample]',
            'slug' => 'the-opus-pinnacle-suites',
            'category' => 'Commercial',
            'location' => 'Madhapur Tech Corridor',
            'price' => 320000000,
            'price_display' => '₹32.00 Cr',
            'area_sqft' => 24000,
            'bedrooms' => 0,
            'bathrooms' => 8,
            'description' => 'Premium corporate floor plates ready for luxury brand flagships, boutique legal chambers, or international fintech hubs.',
            'status' => 'Sold',
            'is_featured' => 0,
            'cover_image' => 'assets/img/property-6.webp'
        ]
    ];
}

