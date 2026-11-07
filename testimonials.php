<?php
/**
 * Magaram Real Estate - Testimonials Management
 */
require_once __DIR__ . '/includes/auth.php';

$activeTab = 'testimonials';
$adminTitle = 'Client Testimonials & Endorsements';
$csrfToken = generate_csrf_token();
$error = '';

$db = get_db();

if (isset($_GET['action']) && $db) {
    $action = $_GET['action'];
    $tid = (int)($_GET['id'] ?? 0);

    if ($action === 'approve' && $tid > 0) {
        $db->prepare("UPDATE testimonials SET is_approved = 1 WHERE id = :id")->execute(['id' => $tid]);
        set_flash('success', 'Testimonial approved for public display.');
        redirect(BASE_URL . '/testimonials.php');
    }
    if ($action === 'unapprove' && $tid > 0) {
        $db->prepare("UPDATE testimonials SET is_approved = 0 WHERE id = :id")->execute(['id' => $tid]);
        set_flash('info', 'Testimonial set to hidden.');
        redirect(BASE_URL . '/testimonials.php');
    }
    if ($action === 'delete' && $tid > 0) {
        $db->prepare("DELETE FROM testimonials WHERE id = :id")->execute(['id' => $tid]);
        set_flash('success', 'Testimonial deleted.');
        redirect(BASE_URL . '/testimonials.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired.';
    } else {
        $name = clean_string($_POST['name'] ?? '');
        $designation = clean_string($_POST['designation'] ?? '');
        $text = clean_string($_POST['text'] ?? '');
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $isApproved = !empty($_POST['is_approved']) ? 1 : 0;
        $isSample = !empty($_POST['is_sample']) ? 1 : 0;

        if (empty($name) || empty($text)) {
            $error = 'Client name and review text are required.';
        } else {
            $ins = $db->prepare("
                INSERT INTO testimonials (name, designation, text, rating, is_approved, is_sample, created_at)
                VALUES (:name, :desig, :text, :rating, :app, :sample, NOW())
            ");
            $ins->execute([
                'name'    => $name,
                'desig'   => $designation,
                'text'    => $text,
                'rating'  => $rating,
                'app'     => $isApproved,
                'sample'  => $isSample
            ]);
            set_flash('success', 'New testimonial recorded.');
            redirect(BASE_URL . '/testimonials.php');
        }
    }
}

$testimonials = [];
if ($db) {
    try {
        $testimonials = $db->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Add Form -->
    <div class="admin-card">
        <h3 style="font-size: 1.15rem; margin-bottom: 16px; color: var(--text-cream);">Add Client Review</h3>
        <p style="font-size: 0.8rem; color: var(--admin-muted); margin-bottom: 20px;">
            Content rule: Add only real, permitted reviews. Mark sample reviews clearly.
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="luxury-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">

            <div class="form-group">
                <label for="t-name">Client Name *</label>
                <input type="text" id="t-name" name="name" class="form-control" placeholder="e.g. Vikramaditya Rao" required>
            </div>

            <div class="form-group">
                <label for="t-desig">Designation / Background</label>
                <input type="text" id="t-desig" name="designation" class="form-control" placeholder="Founder, Apex Capital">
            </div>

            <div class="form-group">
                <label for="t-rating">Rating (Stars)</label>
                <select id="t-rating" name="rating" class="form-control">
                    <option value="5">5 Stars (Masterpiece)</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                </select>
            </div>

            <div class="form-group">
                <label for="t-text">Testimonial Quote *</label>
                <textarea id="t-text" name="text" class="form-control" rows="4" placeholder="Client reflections on discretion, transaction speed, estate quality..." required></textarea>
            </div>

            <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                <label class="checkbox-item">
                    <input type="checkbox" name="is_approved" value="1" checked>
                    <span><strong>Approve for Public Display</strong></span>
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="is_sample" value="1">
                    <span style="color: var(--admin-muted);">Mark as Sample Data</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">
                Save Testimonial
            </button>
        </form>
    </div>

    <!-- Testimonials List -->
    <div class="admin-card">
        <h3 style="font-size: 1.15rem; margin-bottom: 20px; color: var(--text-cream);">Review Registry (<?= count($testimonials); ?>)</h3>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Quote</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($testimonials)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--admin-muted); padding: 30px;">
                            No testimonials recorded yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($testimonials as $t): ?>
                        <tr>
                            <td>
                                <strong><?= e($t['name']); ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--admin-muted);"><?= e($t['designation']); ?></span><br>
                                <span style="color: var(--gold-bright); font-size: 0.8rem;"><?= str_repeat('&#9733;', (int)$t['rating']); ?></span>
                                <?php if (!empty($t['is_sample'])): ?>
                                    <span class="sample-badge" style="display: block; width: fit-content; margin-top: 4px;">Sample</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 320px; font-size: 0.85rem; color: var(--admin-muted); line-height: 1.5;">
                                &ldquo;<?= e($t['text']); ?>&rdquo;
                            </td>
                            <td>
                                <?php if ($t['is_approved']): ?>
                                    <span class="badge badge-completed">Approved</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(239,68,68,0.2); color: #f87171;">Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if ($t['is_approved']): ?>
                                    <a href="?action=unapprove&id=<?= $t['id']; ?>" class="btn btn-outline btn-sm" style="padding: 3px 8px; font-size: 0.72rem;">Hide</a>
                                <?php else: ?>
                                    <a href="?action=approve&id=<?= $t['id']; ?>" class="btn btn-primary btn-sm" style="padding: 3px 8px; font-size: 0.72rem;">Approve</a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?= $t['id']; ?>" style="color: #f87171; margin-left: 8px; font-size: 0.75rem;" onclick="return confirm('Delete testimonial?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

