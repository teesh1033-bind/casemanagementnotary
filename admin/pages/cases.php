<?php
require_once __DIR__ . '/../core/bootstrap.php';

Auth::requireAdmin();

$pageTitle = 'Cases';
$pageSubtitle = 'Track and manage all client cases';
$cases = getAllCases();

require __DIR__ . '/../includes/header.php';
?>

<div class="saas-card">
    <div class="saas-card-header">
        <div>
            <h2 class="saas-card-title">Case Management</h2>
            <p class="saas-card-subtitle"><?= count($cases) ?> total cases</p>
        </div>
    </div>
    <div class="table-toolbar">
        <div class="table-search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control form-control-sm" id="tableSearch" placeholder="Search cases...">
        </div>
        <select class="form-select form-select-sm table-filter" id="statusFilter">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="waiting_for_client">Waiting</option>
            <option value="completed">Completed</option>
            <option value="closed">Closed</option>
        </select>
    </div>
    <div class="card-body p-0">
        <?php if (empty($cases)): ?>
            <div class="empty-state py-5">
                <i class="bi bi-briefcase"></i>
                <p>No cases found. Create a case to get started.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table saas-table mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Case #</th>
                            <th>Title</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Fee</th>
                            <th>Priority</th>
                            <th>Deadline</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $case): ?>
                            <tr data-status="<?= e($case['status']) ?>">
                                <td><strong><?= e($case['case_number']) ?></strong></td>
                                <td>
                                    <div class="case-cell">
                                        <strong><?= e($case['title']) ?></strong>
                                        <?php if ($case['company_name']): ?>
                                            <small><?= e($case['company_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= e(clientFullName($case)) ?></td>
                                <td><?= e($case['service_type']) ?></td>
                                <td><?= formatCurrency((float) $case['service_fee']) ?></td>
                                <td><?= priorityBadge($case['priority']) ?></td>
                                <td><?= formatDate($case['deadline']) ?></td>
                                <td><?= statusBadge($case['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
