<div class="wpdh-dbtable">
    <h1><?php echo esc_html($table_title); ?></h1>

    <!-- actions -->
    <div class="wpdh-actions">
        <code>
            <?php echo esc_html($sql); ?>
        </code>
        <button class="button toggle_create button-primary" type="button" data-target="wpdh-filter-search-form">Filter</button>
        <button class="button toggle_filter_search" type="button" data-target="wpdh-create-form">Create new</button>
    </div>

    <!-- Filter + Search Form -->
    <form method="get" class="wpdh-filter-search-form hidden">
        <h3>Filter / Search</h3>
        <input type="hidden" name="page" value="<?= esc_attr($table_name) ?>">

        <!-- Multi-column search -->
        <div class="wpdh-field-wrap">
            <?php foreach ($fields as $col => $fieldMeta):
                $column = $fieldMeta['column'];
                $dbMeta = $fieldMeta['db'];

                // Type ưu tiên DbColumn, fallback DB
                // $type = method_exists($column, 'getType')
                //     ? $column->getType()
                //     : ($dbMeta['type'] ?? 'text');
                $type = $dbMeta['type'] ?? 'text';
            ?>
                <div class="wpdh-field">
                    <label for="search_<?= esc_attr($col) ?>">
                        <?= esc_html($col); ?>
                        <small><?= esc_html($type) ?></small>
                    </label>
                    <br>
                    <input type="text" id="search_<?= esc_attr($col) ?>" name="s[<?= esc_attr($col) ?>]" value="<?= esc_attr($_GET['s'][$col] ?? '') ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Search Logic -->
        <div>
            <?php $s_logic = $_GET['s_logic'] ?? 'AND' ?>
            <label>
                <input type="radio" name="s_logic" value="AND" <?= ($s_logic === 'AND') ? 'checked' : '' ?>>
                Match all (AND)
            </label>
            <label>
                <input type="radio" name="s_logic" value="OR" <?= ($s_logic === 'OR') ? 'checked' : '' ?>>
                Match any (OR)
            </label>
        </div>

        <!-- Filters -->
        <div>
            <!-- per_page -->
            <label>
                Per page:
                <select name="per_page">
                    <?php
                    $per_page_options = [10, 25, 50, 100];
                    $current_per_page = intval($_GET['per_page'] ?? 50);
                    foreach ($per_page_options as $opt):
                    ?>
                        <option value="<?= $opt ?>" <?= $opt === $current_per_page ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- order_by -->
            <label>
                Order by:
                <select name="order_by">
                    <?php
                    $current_order_by = $_GET['order_by'] ?? 'id';
                    foreach ($fields as $col => $fieldMeta):
                    ?>
                        <option value="<?= esc_attr($col) ?>" <?= $col === $current_order_by ? 'selected' : '' ?>>
                            <?= esc_html($col) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- order_dir -->
            <label>
                Direction:
                <select name="order_dir">
                    <?php
                    $current_order_dir = strtoupper($_GET['order_dir'] ?? 'DESC');
                    foreach (['ASC', 'DESC'] as $dir):
                    ?>
                        <option value="<?= $dir ?>" <?= $dir === $current_order_dir ? 'selected' : '' ?>><?= $dir ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <!-- Submit -->
        <div>
            <button class="button" type="submit">Apply</button>
        </div>
    </form>

    <!-- Create Form -->
    <form method="post" class="wpdh-create-form hidden">
        <h3>Create</h3>
        <input type="hidden" name="wpdh_action" value="create">

        <!-- fields -->
        <div class="wpdh-field-wrap">
            <?php foreach ($fields as $col => $fieldMeta):
                $column = $fieldMeta['column'];
                $dbMeta = $fieldMeta['db'];

                // type ưu tiên DbColumn
                // $type = method_exists($column, 'getType')
                //     ? $column->getType()
                //     : ($dbMeta['type'] ?? 'text');
                $type = $dbMeta['type'] ?? 'text';

                if (method_exists($column, 'isAutoIncrement') && $column->isAutoIncrement()):
            ?>
                    <div class="wpdh-field">
                        <div>
                            <label>
                                <?= esc_html($col); ?>
                                <small><?= esc_html($type) ?></small>
                            </label>
                        </div>
                        <input type="text" disabled value="AUTO">
                    </div>
                <?php
                    continue;
                endif;
                ?>
                <div class="wpdh-field">
                    <div>
                        <label for="create_<?= esc_attr($col) ?>">
                            <?= esc_html($col); ?>
                            <small><?= esc_html($type) ?></small>
                        </label>
                    </div>
                    <input type="text" id="create_<?= esc_attr($col) ?>" name="<?= esc_attr($col) ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <div>
            <button class="button" type="submit">Create new</button>
        </div>
    </form>

    <!-- count -->
    <div class="wpdh-count">
        Found <?php echo esc_html($total_items) ?> records
    </div>

    <!-- Records Table -->
    <div class="wpdh-table">
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="wpdh-select-all">
                    </th>
                    <?php foreach ($fields as $col => $fieldMeta): ?>
                        <th><?php echo esc_html($col); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="wpdh-select-row" name="wpdh_selected[]" value="<?= esc_attr($row->id) ?>">
                            </td>
                            <?php foreach ($fields as $col => $fieldMeta): ?>
                                <td>
                                    <div class="wpdh-db-cell-wrap">
                                        <span class="wpdh-db-cell-value">
                                            <?php echo esc_html($row->$col ?? ''); ?>
                                        </span>
                                        <div class="wpdh-db-cell-form hidden" data-record-id="<?= esc_attr($row->id) ?>" data-column-name="<?= esc_attr($col) ?>" data-table-name="<?= esc_attr($table_name) ?>">
                                            <textarea rows="5" class="wpdh-db-input" name="" id=""><?= esc_attr($row->$col ?? '') ?></textarea>
                                            <br>
                                            <button type=" button" class="button button-primary wpdh-db-save">Save</button>
                                            <span class="wpdh-db-status"></span>
                                        </div>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="wpdb-pagination">
        <div class="wpdh-bulk-actions">
            <form method="post" id="wpdh-bulk-form">
                <select name="wpdh_bulk_action">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="hidden" name="wpdh_selected_ids" id="wpdh-selected-ids" value="">
                <button type="submit" class="button">Apply</button>
            </form>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $range = 2; // số page trước/sau trang hiện tại
                    $start = max(1, $paged - $range);
                    $end = min($total_pages, $paged + $range);

                    // First / Prev
                    if ($paged > 1):
                        $prev_page = $paged - 1;
                    ?>
                        <a class="button" href="?page=<?= esc_attr($table_name) ?>&paged=1<?= $search_query ? '&' . $search_query : '' ?>">« First</a>
                        <a class="button" href="?page=<?= esc_attr($table_name) ?>&paged=<?= $prev_page ?><?= $search_query ? '&' . $search_query : '' ?>">‹ Prev</a>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++):
                        $class = ($i === $paged) ? 'button current-page' : 'button';
                    ?>
                        <a class="<?= esc_attr($class) ?>" href="?page=<?= esc_attr($table_name) ?>&paged=<?= $i ?><?= $search_query ? '&' . $search_query : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php
                    // Next / Last
                    if ($paged < $total_pages):
                        $next_page = $paged + 1;
                    ?>
                        <a class="button" href="?page=<?= esc_attr($table_name) ?>&paged=<?= $next_page ?><?= $search_query ? '&' . $search_query : '' ?>">Next ›</a>
                        <a class="button" href="?page=<?= esc_attr($table_name) ?>&paged=<?= $total_pages ?><?= $search_query ? '&' . $search_query : '' ?>">Last »</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Drop Table Button -->
    <div class="wpdh-drop">
        <form method="post" class="<?php echo $show_drop_button ? '' : 'hidden'; ?>" onsubmit="return confirm('Are you sure you want to DROP this table? This action cannot be undone.');">
            <input type="hidden" name="wpdh_action" value="drop">
            <button class="button button-secondary">
                Drop Table
            </button>
        </form>
    </div>
</div>