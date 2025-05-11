<?php if (isset($result['users']) && is_array($result['users'])): ?>
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
                          name="email"
                          class="form-control form-control-sm"
                          placeholder="Email"
                          value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                        >
                    </th>
                    <th>
                        <input
                          type="text"
                          name="first_name"
                          class="form-control form-control-sm"
                          placeholder="First Name"
                          value="<?= htmlspecialchars($_GET['first_name'] ?? '') ?>"
                        >
                    </th>
                    <th>
                        <input
                          type="text"
                          name="last_name"
                          class="form-control form-control-sm"
                          placeholder="Last Name"
                          value="<?= htmlspecialchars($_GET['last_name'] ?? '') ?>"
                        >
                    </th>
                    <th>
                        <input
                          type="date"
                          name="birth_date"
                          class="form-control form-control-sm"
                          placeholder="Birth Date"
                          value="<?= htmlspecialchars($_GET['birth_date'] ?? '') ?>"
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
                        </div>
                    </th>
                </tr>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Birth Date</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['users'] as $row): ?>
                <tr
                  class="clickable-row"
                  data-href="<?= ADMIN_PANEL . 'details/user_settings.php?user_id=' . urlencode($row['id']) ?>"
                >
                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td>
                        <?= htmlspecialchars(
                            mb_strimwidth($row['email'] ?? '', 0, 100, '...')
                        ) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars(
                            mb_strimwidth($row['first_name'] ?? '', 0, 100, '...')
                        ) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars(
                            mb_strimwidth($row['last_name'] ?? '', 0, 100, '...')
                        ) ?>
                    </td>
                    <td><?= htmlspecialchars($row['birth_date'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.clickable-row').forEach(function(row) {
        row.addEventListener('click', function() {
            window.location = this.dataset.href;
        });
    });
});
</script>

<?php else: ?>
    <div class="alert alert-info py-2">No users to display.</div>
<?php endif; ?>
