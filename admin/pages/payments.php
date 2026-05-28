<?php
require_once __DIR__ . '/../core/bootstrap.php';

Auth::requireAdmin();

$pageTitle = 'Payments';
$payments = getAllPayments();
$stats = getDashboardStats();
$pageSubtitle = formatCurrency($stats['total_revenue']) . ' total revenue';

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metric-card">
            <div class="metric-icon metric-icon-success"><i class="bi bi-cash-stack"></i></div>
            <div class="metric-body">
                <span class="metric-label">Total Revenue</span>
                <span class="metric-value metric-value-sm"><?= formatCurrency($stats['total_revenue']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card">
            <div class="metric-icon metric-icon-primary"><i class="bi bi-calendar3"></i></div>
            <div class="metric-body">
                <span class="metric-label">This Month</span>
                <span class="metric-value metric-value-sm"><?= formatCurrency($stats['monthly_revenue']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card">
            <div class="metric-icon metric-icon-warning"><i class="bi bi-hourglass-split"></i></div>
            <div class="metric-body">
                <span class="metric-label">Pending Invoices</span>
                <span class="metric-value"><?= number_format($stats['pending_invoices']) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-header">
        <div>
            <h2 class="saas-card-title">Payment History</h2>
            <p class="saas-card-subtitle"><?= count($payments) ?> transactions</p>
        </div>
    </div>
    <div class="table-toolbar">
        <div class="table-search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control form-control-sm" id="tableSearch" placeholder="Search payments...">
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($payments)): ?>
            <div class="empty-state py-5">
                <i class="bi bi-credit-card"></i>
                <p class="mb-0">No payments recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table saas-table mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Paid At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <span class="table-primary"><?= e($payment['invoice_number']) ?></span>
                                    <span class="table-secondary d-block"><?= formatCurrency((float) $payment['invoice_total']) ?></span>
                                </td>
                                <td><?= e(clientFullName($payment)) ?></td>
                                <td><span class="table-primary"><?= formatCurrency((float) $payment['amount']) ?></span></td>
                                <td><?= e(ucwords(str_replace('_', ' ', $payment['payment_method']))) ?></td>
                                <td><?= paymentStatusBadge(paymentStatusValue($payment)) ?></td>
                                <td class="text-muted"><?= formatDateTime($payment['paid_at'] ?? $payment['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
