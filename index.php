<?php
/**
 * Magaram Real Estate - Executive Admin Suite Entrypoint
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_admin_logged_in()) {
    redirect(BASE_URL . '/dashboard.php');
} else {
    redirect(BASE_URL . '/login.php');
}
