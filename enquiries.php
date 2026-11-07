<?php
/**
 * Magaram Real Estate - Enquiries & Leads Management
 */
require_once __DIR__ . '/includes/auth.php';

$activeTab = 'enquiries';
$adminTitle = 'Client Enquiries & Lead Pipeline';
$csrfToken = generate_csrf_token();

$db = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=magaram_leads_' . date('Y-m-d_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Lead ID', 'Date', 'Type', 'Client Name', 'Phone', 'Email', 'Preferred Date', 'Property Interest', 'Status', 'Message', 'Internal Notes']);

    if ($db) {
        $stmt = $db->query("
            SELECT e.*, p.title AS property_title 
            FROM enquiries e
            LEFT JOIN properties p ON e.property_id = p.id
            ORDER BY e.created_at DESC
        ");
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $row['type'],
                $row['name'],
                $row['phone'],
                $row['email'],
                $row['preferred_date'] ?? 'N/A',
                $row['property_title'] ?? 'General Portfolio',
                $row['status'],
                $row['message'],
                $row['notes']
            ]);
        }
    }
    fclose($output);
    exit;
}

// Handle Status or Note Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security token invalid.');
    } else {
        $enquiryId = (int)($_POST['enquiry_id'] ?? 0);
        $status = clean_string($_POST['status'] ?? 'New');
        $notes = clean_string($_POST['notes'] ?? '');

        if ($db && $enquiryId > 0) {
            try {
                $stmt = $db->prepare("UPDATE enquiries SET status = :status, notes = :notes WHERE id = :id");
                $stmt->execute(['status' => $status, 'notes' => $notes, 'id' => $enquiryId]);
                set_flash('success', 'Lead record updated successfully.');
            } catch (Exception $e) {
                set_flash('danger', 'Error updating lead: ' . $e->getMessage());
            }
        }
    }
    redirect(BASE_URL . '/enquiries.php');
}

$filterStatus = clean_string($_GET['status'] ?? '');
$filterType = clean_string($_GET['type'] ?? '');

$enquiries = [];
if ($db) {
    try {
        $where = ["1=1"];
        $params = [];

        if (!empty($filterStatus)) {
            $where[] = "e.status = :status";
            $params['status'] = $filterStatus;
        }

        if (!empty($filterType)) {
            $where[] = "e.type = :type";
            $params['type'] = $filterType;
        }

        $whereClause = implode(" AND ", $where);
        $stmt = $db->prepare("
            SELECT e.*, p.title AS property_title 
            FROM enquiries e
            LEFT JOIN properties p ON e.property_id = p.id
            WHERE {$whereClause}
            ORDER BY e.created_at DESC
        ");
        $stmt->execute($params);
        $enquiries = $stmt->fetchAll();
    } catch (Exception $e) {
        $enquiries = [];
    }
} else {
    $enquiries = [
        ['id' => 1, 'name' => 'Vikramaditya Rao', 'phone' => '+91 98765 43210', 'email' => 'vikram@apex.in', 'type' => 'Site Visit', 'preferred_date' => '2026-11-15', 'status' => 'New', 'property_title' => 'The Sovereign Penthouse', 'message' => 'Need helicopter pad access and private floor clearance.', 'notes' => 'Awaiting schedule confirmation', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'name' => 'Dr. Aisha Merchant', 'phone' => '+91 98111 22334', 'email' => 'aisha@hospital.org', 'type' => 'Brochure', 'preferred_date' => null, 'status' => 'New', 'property_title' => 'The Elysian Garden Villa', 'message' => 'Downloaded digital prospectus', 'notes' => '', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
        ['id' => 3, 'name' => 'Karan Singhania', 'phone' => '+91 99999 88888', 'email' => 'karan@singhania.com', 'type' => 'Enquiry', 'preferred_date' => null, 'status' => 'Contacted', 'property_title' => 'Prestige Crown Commercial', 'message' => 'Looking for 30,000 sq.ft single-tenant corporate block.', 'notes' => 'Sent introductory portfolio pitch deck', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]
    ];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h3 style="font-size: 1.25rem; margin: 0; color: var(--text-cream);">Client Inquiries & Leads (<?= count($enquiries); ?>)</h3>
            <p style="font-size: 0.85rem; color: var(--admin-muted); margin-top: 4px;">Track client appointments, brochure requests, and general consultations.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="?export=csv" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="background: rgba(0,0,0,0.3); padding: 16px; border-radius: 6px; border: 1px solid var(--admin-border); margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="f-status" style="font-size: 0.8rem; color: var(--gold-bright); text-transform: uppercase;">Status:</label>
                <select name="status" id="f-status" class="form-control" style="padding: 6px 12px; width: auto;">
                    <option value="">All Statuses</option>
                    <option value="New" <?= $filterStatus === 'New' ? 'selected' : ''; ?>>New</option>
                    <option value="Contacted" <?= $filterStatus === 'Contacted' ? 'selected' : ''; ?>>Contacted</option>
                    <option value="Completed" <?= $filterStatus === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="f-type" style="font-size: 0.8rem; color: var(--gold-bright); text-transform: uppercase;">Type:</label>
                <select name="type" id="f-type" class="form-control" style="padding: 6px 12px; width: auto;">
                    <option value="">All Types</option>
                    <option value="Enquiry" <?= $filterType === 'Enquiry' ? 'selected' : ''; ?>>Enquiry</option>
                    <option value="Site Visit" <?= $filterType === 'Site Visit' ? 'selected' : ''; ?>>Site Visit</option>
                    <option value="Brochure" <?= $filterType === 'Brochure' ? 'selected' : ''; ?>>Brochure</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 16px;">Apply Filter</button>
            <?php if (!empty($filterStatus) || !empty($filterType)): ?>
                <a href="enquiries.php" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Enquiries Table -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Client Name & Contact</th>
                <th>Intent</th>
                <th>Property</th>
                <th>Message / Date</th>
                <th>Status</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($enquiries)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--admin-muted); padding: 40px;">
                        No enquiries matching the filter criteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($enquiries as $row): ?>
                    <tr>
                        <td style="white-space: nowrap; font-size: 0.82rem;">
                            <?= date('d M Y', strtotime($row['created_at'])); ?><br>
                            <span style="color: var(--admin-muted);"><?= date('h:i A', strtotime($row['created_at'])); ?></span>
                        </td>
                        <td>
                            <strong style="color: var(--text-cream); font-size: 0.95rem;"><?= e($row['name']); ?></strong>
                            <div style="font-size: 0.82rem; color: var(--gold-bright);">
                                <a href="tel:<?= e($row['phone']); ?>"><?= e($row['phone']); ?></a>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--admin-muted);">
                                <a href="mailto:<?= e($row['email']); ?>"><?= e($row['email']); ?></a>
                            </div>
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 0.75rem; background: rgba(198,161,91,0.15); color: var(--gold-bright); font-weight: 600;">
                                <?= e($row['type']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; font-weight: 500;">
                                <?= e($row['property_title'] ?? 'General Portfolio'); ?>
                            </span>
                        </td>
                        <td style="max-width: 250px;">
                            <?php if (!empty($row['preferred_date'])): ?>
                                <div style="font-size: 0.78rem; color: var(--gold-bright); margin-bottom: 4px;">
                                    Requested: <?= date('d M Y', strtotime($row['preferred_date'])); ?>
                                </div>
                            <?php endif; ?>
                            <div style="font-size: 0.82rem; color: var(--admin-muted); line-height: 1.4;">
                                <?= nl2br(e($row['message'])); ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-<?= strtolower($row['status']); ?>">
                                <?= e($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: flex; flex-direction: column; gap: 6px;">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                <input type="hidden" name="enquiry_id" value="<?= $row['id']; ?>">
                                <select name="status" class="form-control" style="padding: 4px 8px; font-size: 0.75rem;">
                                    <option value="New" <?= $row['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                    <option value="Contacted" <?= $row['status'] === 'Contacted' ? 'selected' : ''; ?>>Contacted</option>
                                    <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <input type="text" name="notes" class="form-control" placeholder="Internal notes..." value="<?= e($row['notes'] ?? ''); ?>" style="padding: 4px 8px; font-size: 0.75rem;">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding: 3px 8px; font-size: 0.7rem;">
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

