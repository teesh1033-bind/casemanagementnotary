<?php
require_once __DIR__ . '/../core/bootstrap.php';

Auth::requireAdmin();

$pageTitle = 'Appointments';
$appointments = getAllAppointments();
$stats = getDashboardStats();
$pageSubtitle = $stats['upcoming_appointments'] . ' upcoming';

require __DIR__ . '/../includes/header.php';
?>

<div class="saas-card">
    <div class="saas-card-header">
        <div>
            <h2 class="saas-card-title">Appointment Calendar</h2>
            <p class="saas-card-subtitle"><?= count($appointments) ?> total appointments</p>
        </div>
    </div>
    <div class="table-toolbar">
        <div class="table-search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control form-control-sm" id="tableSearch" placeholder="Search appointments...">
        </div>
        <select class="form-select form-select-sm table-filter" id="statusFilter">
            <option value="">All statuses</option>
            <option value="scheduled">Scheduled</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
    <div class="card-body p-0">
        <?php if (empty($appointments)): ?>
            <div class="empty-state py-5">
                <i class="bi bi-calendar3"></i>
                <p class="mb-0">No appointments scheduled yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table saas-table mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Title</th>
                            <th>Client</th>
                            <th>Case</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr data-status="<?= e($appt['status']) ?>">
                                <td>
                                    <span class="table-primary"><?= formatDate(appointmentStart($appt)) ?></span>
                                    <span class="table-secondary d-block">
                                        <?= formatDateTime(appointmentStart($appt), 'g:i A') ?>
                                    </span>
                                </td>
                                <td><?= e($appt['title']) ?></td>
                                <td><?= e(clientFullName($appt)) ?></td>
                                <td class="text-muted"><?= e($appt['case_number'] ?: '—') ?></td>
                                <td><?= statusBadge($appt['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
