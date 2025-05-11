<?php if (isset($result['assigned_certificates']) && is_array($result['assigned_certificates'])): ?>
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
                          type="text"
                          name="certificate_id"
                          class="form-control form-control-sm"
                          placeholder="Certificate ID"
                          value="<?= htmlspecialchars($_GET['certificate_id'] ?? '') ?>"
                        >
                    </th>
                    <th>
                        <input
                          type="text"
                          name="certificate_title_assigned"
                          class="form-control form-control-sm"
                          placeholder="Certificate Title"
                          value="<?= htmlspecialchars($_GET['certificate_title_assigned'] ?? '') ?>"
                        >
                    </th>
                    <th>
                        <div class="input-group input-group-sm">
                            <input
                              type="date"
                              name="awarded_at"
                              class="form-control"
                              placeholder="Awarded At"
                              value="<?= htmlspecialchars($_GET['awarded_at'] ?? '') ?>"
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
                    <th>Certificate ID</th>
                    <th>Certificate Title</th>
                    <th>Awarded At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['assigned_certificates'] as $row): ?>
                <tr
                  class="clickable-row"
                  data-href="<?= ADMIN_PANEL . 'details/assigned_certificate.php?certificate_id=' . urlencode($row['id']) ?>"
                >
                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($row['email'] ?? '', 0, 100, '...')) ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($row['first_name'] ?? '', 0, 100, '...')) ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($row['last_name'] ?? '', 0, 100, '...')) ?></td>
                    <td><?= htmlspecialchars($row['certificate_id']) ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($row['certificate_title'] ?? '', 0, 100, '...')) ?></td>
                    <td><?= htmlspecialchars($row['awarded_at'] ?? '') ?></td>
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
    <div class="alert alert-info py-2">No assigned certificates to display.</div>
<?php endif; ?>
