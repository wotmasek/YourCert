<?php if (isset($result['certificates']) && is_array($result['certificates'])): ?>
<div class="table-responsive">
    <form id="filter-form" method="GET" action="">
        <!-- preserve active tab -->
        <input type="hidden" name="data_type" value="<?= htmlspecialchars($data_type) ?>">

        <table class="table table-sm table-striped table-hover align-middle mb-2">
            <thead>
                <tr>
                    <th></th>
                    <th>
                        <input
                          type="text"
                          name="certificate_title"
                          class="form-control form-control-sm"
                          placeholder="Title"
                          value="<?= htmlspecialchars($_GET['certificate_title'] ?? '') ?>"
                        >
                    </th>
                    <th>
                        <input
                          type="text"
                          name="description"
                          class="form-control form-control-sm"
                          placeholder="Description"
                          value="<?= htmlspecialchars($_GET['description'] ?? '') ?>"
                        >
                    </th>
                    <th>
                        <input
                          type="date"
                          name="valid_until"
                          class="form-control form-control-sm"
                          placeholder="Valid Until"
                          value="<?= htmlspecialchars($_GET['valid_until'] ?? '') ?>"
                        >
                    </th>
                    <th>
                        <div class="input-group input-group-sm">
                            <input
                              type="date"
                              name="created_at"
                              class="form-control"
                              placeholder="Created At"
                              value="<?= htmlspecialchars($_GET['created_at'] ?? '') ?>"
                            >
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <button type="button"
                                    class="btn btn-success btn-sm"
                                    onclick="window.location.href='<?= ADMIN_PANEL ?>create/certificate.php';">
                              + Create
                            </button>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Valid Until</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['certificates'] as $row): ?>
                <tr
                  class="clickable-row"
                  data-href="<?= ADMIN_PANEL . 'details/certificate.php?certificate_id=' . urlencode($row['id']) ?>"
                >
                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td>
                        <?= htmlspecialchars(
                            mb_strimwidth($row['title'] ?? '', 0, 100, '...')
                        ) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars(
                            mb_strimwidth($row['description'] ?? '', 0, 100, '...')
                        ) ?>
                    </td>
                    <td><?= htmlspecialchars($row['valid_until'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', () => {
            window.location = row.dataset.href;
        });
    });
});
</script>

<?php else: ?>
    <button type="button"
            class="btn btn-success btn-sm mb-2"
            onclick="window.location.href='<?= ADMIN_PANEL ?>create/certificate.php';">
      + Create
    </button>
    <div class="alert alert-info py-2">No certificates to display.</div>
<?php endif; ?>
