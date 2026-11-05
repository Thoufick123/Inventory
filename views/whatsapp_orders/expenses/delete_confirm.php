<div class="container py-5">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 560px;">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                <i class="bi bi-trash fs-4"></i>
            </div>
            <h1 class="h4 fw-bold mb-2">Confirm Expense Deletion</h1>
            <p class="text-muted mb-4">
                Delete voucher <strong><?= e($expense['expense_number']) ?></strong> for
                <strong>₹<?= number_format((float)$expense['total_cost'], 2) ?></strong>?
                This action cannot be undone.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="<?= url('warehouse/whatsapp-orders/expenses') ?>" class="btn btn-outline-secondary">Cancel</a>
                <form method="POST" action="<?= url('warehouse/whatsapp-orders/expenses/delete/' . (int)$expense['id']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Delete Expense</button>
                </form>
            </div>
        </div>
    </div>
</div>
