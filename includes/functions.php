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
        'tagline' => 'Curating Extraordinary Living Spaces in Coimbatore',
        'contact_email' => 'contact@magaramrealestate.com',
        'contact_phone' => '+91 98430 12345',
        'office_address' => 'Magaram Towers, Level 5, Race Course Road, Coimbatore, Tamil Nadu - 641018',
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
        'about_preview' => 'At Magaram Real Estate, we bridge discerning patrons with timeless architectural masterworks across Coimbatore and the Western Ghats. Our portfolio is curated exclusively for those who view real estate not simply as assets, but as legacies of distinction.',
        'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3916.326292398491!2d76.9712165757467!3d11.01183348915159!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba859ad33cfd2db%3A0x8aa50c60f4ff2b7!2sRace%20Course%2C%20Coimbatore%2C%20Tamil%20Nadu!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin'
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
 * Send Email Notification (Native SMTP with TLS & mail() fallback)
 */
function send_admin_notification(string $subject, string $body): bool {
    require_once __DIR__ . '/smtp.php';
    $mailer = new LuxuryMailer();
    return $mailer->send(ADMIN_EMAIL, $subject, $body);
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
            'slug' => 'the-sovereign-penthouse-coimbatore',
            'category' => 'Apartment',
            'location' => 'Race Course, Coimbatore',
            'price' => 85000000,
            'price_display' => '₹8.50 Cr',
            'area_sqft' => 5600,
            'bedrooms' => 4,
            'bathrooms' => 5,
            'description' => 'An ultra-exclusive double-height penthouse overlooking the lush green canopy of Race Course Road in Coimbatore. Features private biometric elevator access, an expansive open terrace with sky deck plunge pool, imported Calacatta marble, and 360-degree skyline vistas.',
            'status' => 'Available',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-1.svg'
        ],
        [
            'id' => 2,
            'title' => 'The Elysian Sanctuary Villa [Sample]',
            'slug' => 'the-elysian-sanctuary-villa-vadavalli',
            'category' => 'Villa',
            'location' => 'Vadavalli Foothills, Coimbatore',
            'price' => 125000000,
            'price_display' => '₹12.50 Cr',
            'area_sqft' => 8200,
            'bedrooms' => 5,
            'bathrooms' => 6,
            'description' => 'Set against the serene Western Ghats foothills in Vadavalli, this contemporary architectural estate features landscaped courtyards, natural stone facades, private temperature-controlled pool, home automation, and staff quarters.',
            'status' => 'Available',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-2.svg'
        ],
        [
            'id' => 3,
            'title' => 'Prestige Apex Commercial Tower [Sample]',
            'slug' => 'prestige-apex-commercial-peelamedu',
            'category' => 'Commercial',
            'location' => 'Avinashi Road, Peelamedu, Coimbatore',
            'price' => 360000000,
            'price_display' => '₹36.00 Cr',
            'area_sqft' => 28000,
            'bedrooms' => 0,
            'bathrooms' => 10,
            'description' => 'Grade-A corporate commercial tower positioned along Coimbatore\'s prime Avinashi Road arterial corridor. LEED Gold certified, high-speed destination elevators, column-free floor plates, and executive boardroom suites.',
            'status' => 'Available',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-3.svg'
        ],
        [
            'id' => 4,
            'title' => 'The Aurum Signature Suites [Sample]',
            'slug' => 'the-aurum-signature-suites-rs-puram',
            'category' => 'Apartment',
            'location' => 'R.S. Puram, Coimbatore',
            'price' => 68000000,
            'price_display' => '₹6.80 Cr',
            'area_sqft' => 4200,
            'bedrooms' => 4,
            'bathrooms' => 4,
            'description' => 'A boutique development of private single-floor residences in the heart of heritage R.S. Puram. Featuring soundproof acoustic glazing, private master suite with custom walk-in dressing chambers, and Italian modular fittings.',
            'status' => 'Available',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-4.svg'
        ],
        [
            'id' => 5,
            'title' => 'Serene Palm Orchards Estate [Sample]',
            'slug' => 'serene-palm-orchards-thondamuthur',
            'category' => 'Villa',
            'location' => 'Siruvani Foothills, Coimbatore',
            'price' => 145000000,
            'price_display' => '₹14.50 Cr',
            'area_sqft' => 9500,
            'bedrooms' => 6,
            'bathrooms' => 7,
            'description' => 'Sprawling private gated farm estate with organic coconut orchards, Olympic-length private swimming pool, independent guest clubhouse pavilion, and 24/7 security perimeter.',
            'status' => 'Upcoming',
            'is_featured' => 1,
            'cover_image' => 'assets/img/property-5.svg'
        ],
        [
            'id' => 6,
            'title' => 'The Opus Tech Pinnacle [Sample]',
            'slug' => 'the-opus-tech-pinnacle-saravanampatti',
            'category' => 'Commercial',
            'location' => 'Saravanampatti IT Corridor, Coimbatore',
            'price' => 240000000,
            'price_display' => '₹24.00 Cr',
            'area_sqft' => 22000,
            'bedrooms' => 0,
            'bathrooms' => 8,
            'description' => 'High-yield commercial tech headquarters designed for global SaaS and engineering enterprises in Coimbatore\'s premier IT hub.',
            'status' => 'Sold',
            'is_featured' => 0,
            'cover_image' => 'assets/img/property-6.svg'
        ]
    ];
}

