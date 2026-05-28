<?php
require_once __DIR__ . '/../core/bootstrap.php';

Auth::requireAdmin();

$pageTitle = 'Payments';
$payments = getAllPayments();
$pendingInvoices = getPendingInvoices();
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
    <div class="saas-card-header appointment-list-header">
        <div>
            <h2 class="saas-card-title">Payment History</h2>
            <p class="saas-card-subtitle mb-0"><?= count($payments) ?> transactions</p>
        </div>
        <?php if (!empty($pendingInvoices)): ?>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                <i class="bi bi-plus-lg"></i> Record Payment
            </button>
        <?php endif; ?>
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
                <table class="table saas-table appointment-list-table mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Paid At</th>
                            <th>Receipt</th>
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
                                <td>
                                    <?php if (!empty($payment['receipt_id'])): ?>
                                        <a href="<?= url('actions/receipt-download.php?id=' . (int) $payment['receipt_id']) ?>" class="btn btn-soft btn-sm" target="_blank">
                                            <i class="bi bi-receipt"></i> <?= e($payment['receipt_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($pendingInvoices)): ?>
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= url('actions/payment-action.php') ?>" class="modal-content">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="record_payment">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Invoice</label>
                    <select name="invoice_id" class="form-select" required>
                        <?php foreach ($pendingInvoices as $inv): ?>
                            <option value="<?= (int) $inv['id'] ?>">
                                <?= e($inv['invoice_number']) ?> — <?= e(clientFullName($inv)) ?> — <?= formatCurrency((float) $inv['total']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" placeholder="Leave blank for full invoice amount">
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="stripe">Stripe</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Record & Generate Receipt</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
