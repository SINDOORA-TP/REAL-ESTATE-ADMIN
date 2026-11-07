<?php
/**
 * Magaram Real Estate - Site Settings & Stats Management
 */
require_once __DIR__ . '/includes/auth.php';

$activeTab = 'settings';
$adminTitle = 'Site Configurations & Content';
$csrfToken = generate_csrf_token();
$error = '';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh.';
    } else {
        $settingsToSave = [
            'site_name'         => clean_string($_POST['site_name'] ?? 'Magaram Real Estate'),
            'tagline'           => clean_string($_POST['tagline'] ?? ''),
            'contact_phone'     => clean_string($_POST['contact_phone'] ?? ''),
            'contact_email'     => clean_string($_POST['contact_email'] ?? ''),
            'office_address'    => clean_string($_POST['office_address'] ?? ''),
            'hero_headline_1'   => clean_string($_POST['hero_headline_1'] ?? 'Experience'),
            'hero_headline_2'   => clean_string($_POST['hero_headline_2'] ?? 'Luxury Living'),
            'hero_subtext'      => clean_string($_POST['hero_subtext'] ?? ''),
            'show_stats'        => !empty($_POST['show_stats']) ? '1' : '0',
            'stats_note'        => clean_string($_POST['stats_note'] ?? ''),
            'stat_1_val'        => clean_string($_POST['stat_1_val'] ?? ''),
            'stat_1_lbl'        => clean_string($_POST['stat_1_lbl'] ?? ''),
            'stat_2_val'        => clean_string($_POST['stat_2_val'] ?? ''),
            'stat_2_lbl'        => clean_string($_POST['stat_2_lbl'] ?? ''),
            'stat_3_val'        => clean_string($_POST['stat_3_val'] ?? ''),
            'stat_3_lbl'        => clean_string($_POST['stat_3_lbl'] ?? ''),
            'stat_4_val'        => clean_string($_POST['stat_4_val'] ?? ''),
            'stat_4_lbl'        => clean_string($_POST['stat_4_lbl'] ?? ''),
            'show_testimonials' => !empty($_POST['show_testimonials']) ? '1' : '0',
            'about_preview'     => clean_string($_POST['about_preview'] ?? ''),
            'map_embed_url'     => trim($_POST['map_embed_url'] ?? '')
        ];

        if ($db) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO settings (setting_key, setting_value) 
                    VALUES (:k, :v) 
                    ON DUPLICATE KEY UPDATE setting_value = :v
                ");
                foreach ($settingsToSave as $k => $v) {
                    $stmt->execute(['k' => $k, 'v' => $v]);
                }
                set_flash('success', 'Site settings and counters saved successfully.');
                redirect(BASE_URL . '/settings.php');
            } catch (Exception $e) {
                $error = 'Error saving settings: ' . $e->getMessage();
            }
        } else {
            set_flash('info', 'Database is currently offline.');
            redirect(BASE_URL . '/settings.php');
        }
    }
}

$s = get_all_settings();
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card" style="max-width: 960px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h3 style="font-size: 1.25rem; margin: 0; color: var(--text-cream);">Site Settings & Content Controls</h3>
        <p style="font-size: 0.85rem; color: var(--admin-muted); margin-top: 4px;">Update branding, concierge contact details, hero copy, and stats row visibility.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="luxury-form">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">

        <!-- Branding & Contact -->
        <h4 style="color: var(--gold-bright); border-bottom: 1px solid var(--admin-border); padding-bottom: 8px; margin-top: 10px;">Brand & Concierge Contact</h4>
        
        <div class="form-grid-2">
            <div class="form-group">
                <label for="s-name">Company Brand Name</label>
                <input type="text" id="s-name" name="site_name" class="form-control" value="<?= e($s['site_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="s-tagline">Brand Tagline</label>
                <input type="text" id="s-tagline" name="tagline" class="form-control" value="<?= e($s['tagline']); ?>">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="s-phone">Concierge Phone Number</label>
                <input type="text" id="s-phone" name="contact_phone" class="form-control" value="<?= e($s['contact_phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="s-email">Concierge Email Address</label>
                <input type="email" id="s-email" name="contact_email" class="form-control" value="<?= e($s['contact_email']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="s-address">Office / Chambers Address</label>
            <input type="text" id="s-address" name="office_address" class="form-control" value="<?= e($s['office_address']); ?>" required>
        </div>

        <div class="form-group">
            <label for="s-map">Google Maps Embed URL</label>
            <input type="text" id="s-map" name="map_embed_url" class="form-control" value="<?= e($s['map_embed_url']); ?>">
        </div>

        <!-- Hero Section Copy -->
        <h4 style="color: var(--gold-bright); border-bottom: 1px solid var(--admin-border); padding-bottom: 8px; margin-top: 30px;">Hero Section Copy</h4>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="s-hero1">Headline Top Word (Cream)</label>
                <input type="text" id="s-hero1" name="hero_headline_1" class="form-control" value="<?= e($s['hero_headline_1']); ?>">
            </div>
            <div class="form-group">
                <label for="s-hero2">Headline Accent (Gold Shimmer)</label>
                <input type="text" id="s-hero2" name="hero_headline_2" class="form-control" value="<?= e($s['hero_headline_2']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="s-subtext">Hero Subtitle</label>
            <textarea id="s-subtext" name="hero_subtext" class="form-control" rows="2"><?= e($s['hero_subtext']); ?></textarea>
        </div>

        <!-- Stats Row Settings -->
        <h4 style="color: var(--gold-bright); border-bottom: 1px solid var(--admin-border); padding-bottom: 8px; margin-top: 30px;">Stats Row Configuration</h4>
        
        <div style="background: rgba(0,0,0,0.3); padding: 18px; border-radius: 6px; border: 1px solid var(--admin-border); margin-bottom: 20px;">
            <label class="checkbox-item" style="margin-bottom: 12px;">
                <input type="checkbox" name="show_stats" value="1" <?= (!empty($s['show_stats']) && $s['show_stats'] == '1') ? 'checked' : ''; ?>>
                <strong style="color: var(--gold-bright);">Display Stats Row on Homepage</strong>
            </label>
            <small style="color: var(--admin-muted); display: block; margin-bottom: 16px;">
                Content rule: Only show real numbers, or uncheck the box above to hide the stats row until confirmed.
            </small>

            <div class="form-group" style="margin-bottom: 18px;">
                <label for="s-note">Sample Data Disclaimer Note</label>
                <input type="text" id="s-note" name="stats_note" class="form-control" value="<?= e($s['stats_note'] ?? ''); ?>" placeholder="[Sample Data - Update with Real Figures]">
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div class="form-group">
                    <label>Counter 1 (Value & Label)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="stat_1_val" class="form-control" value="<?= e($s['stat_1_val']); ?>" style="width: 100px;">
                        <input type="text" name="stat_1_lbl" class="form-control" value="<?= e($s['stat_1_lbl']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Counter 2 (Value & Label)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="stat_2_val" class="form-control" value="<?= e($s['stat_2_val']); ?>" style="width: 100px;">
                        <input type="text" name="stat_2_lbl" class="form-control" value="<?= e($s['stat_2_lbl']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Counter 3 (Value & Label)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="stat_3_val" class="form-control" value="<?= e($s['stat_3_val']); ?>" style="width: 100px;">
                        <input type="text" name="stat_3_lbl" class="form-control" value="<?= e($s['stat_3_lbl']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Counter 4 (Value & Label)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" name="stat_4_val" class="form-control" value="<?= e($s['stat_4_val']); ?>" style="width: 100px;">
                        <input type="text" name="stat_4_lbl" class="form-control" value="<?= e($s['stat_4_lbl']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- About Preview & Testimonials -->
        <h4 style="color: var(--gold-bright); border-bottom: 1px solid var(--admin-border); padding-bottom: 8px; margin-top: 30px;">Homepage Preview & Testimonials</h4>

        <div class="form-group">
            <label for="s-about">About Section Narrative</label>
            <textarea id="s-about" name="about_preview" class="form-control" rows="3"><?= e($s['about_preview']); ?></textarea>
        </div>

        <div class="form-group" style="margin-top: 14px;">
            <label class="checkbox-item">
                <input type="checkbox" name="show_testimonials" value="1" <?= (!empty($s['show_testimonials']) && $s['show_testimonials'] == '1') ? 'checked' : ''; ?>>
                <strong>Show Testimonials Section</strong>
            </label>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn btn-primary">Save All Settings</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

