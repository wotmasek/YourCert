<?php if (!empty($result['data']) && is_array($result['data'])): ?>
  <div class="table-responsive">
    <form id="filter-form" method="GET">
      <!-- preserve context -->
      <input type="hidden" name="data_type" value="<?= htmlspecialchars($data_type) ?>">
      <table class="table table-sm table-striped table-hover align-middle mb-0">
        <thead>
          <!-- filter row -->
          <tr>
            <th><input type="text" name="title" class="form-control form-control-sm" placeholder="Title"
                       value="<?= htmlspecialchars($_GET['title'] ?? '') ?>"></th>
            <th><input type="text" name="slug" class="form-control form-control-sm" placeholder="Slug"
                       value="<?= htmlspecialchars($_GET['slug'] ?? '') ?>"></th>
            <th>
              <div class="input-group input-group-sm">
                <input type="date" name="created_at" class="form-control" placeholder="Created"
                       value="<?= htmlspecialchars($_GET['created_at'] ?? '') ?>">
                <button type="submit" class="btn btn-primary">Filter</button>
                <button type="button"
                        class="btn btn-success btn-sm"
                        onclick="window.location.href='<?= ADMIN_PANEL ?>create/post.php';">
                  + Create Post
                </button>
              </div>
            </th>
          </tr>
          <!-- header row -->
          <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Created At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($result['data'] as $post): ?>
          <tr class="clickable-row"
              data-href="<?= ADMIN_PANEL . 'details/post.php?post_id=' . urlencode($post['id']) ?>">
              <td>
                  <?= htmlspecialchars(
                      mb_strimwidth($post['title'] ?? '', 0, 100, '...')
                  ) ?>
              </td>
              <td>
                  <?= htmlspecialchars(
                      mb_strimwidth($post['slug'] ?? '', 0, 100, '...')
                  ) ?>
              </td>
              <td><?= htmlspecialchars($post['created_at'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
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
          onclick="window.location.href='<?= ADMIN_PANEL ?>create/post.php';">
    + Create Post
  </button>
  <div class="alert alert-info py-2">No posts to display.</div>
<?php endif; ?>
