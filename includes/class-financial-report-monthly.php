<?php
if (!defined('ABSPATH')) exit;

class Financial_Report_Monthly extends Financial_Report_Base {

    public function render_page() {
        $year  = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 25;

        $data  = $this->get_workorders($year, $month);
        // Filter by status (category)
        if (!empty($status) && $status !== 'all') {
            $data = array_values(array_filter($data, function($row) use ($status) {
                return function_exists('has_term') ? has_term($status, 'category', $row['id']) : true;
            }));
        }
        $total_items = count($data);
        $total_pages = max(1, ceil($total_items / $per_page));
        $offset = ($paged - 1) * $per_page;

        $paged_data = array_slice($data, $offset, $per_page);

        echo '<div class="wrap"><h1>Monthly Financial Report (' . date('F Y', strtotime("$year-$month-01")) . ')</h1>';

        echo '<form method="get" style="margin-bottom:12px">';
        echo '<input type="hidden" name="page" value="financial-reports-monthly">';
        echo '<label for="month">Month:</label> ';
        echo '<select name="month">';
        for ($m = 1; $m <= 12; $m++) {
            $sel = ($m == $month) ? 'selected' : '';
            echo "<option value='$m' $sel>" . date('F', mktime(0,0,0,$m,1)) . "</option>";
        }
        echo '</select> ';
        echo '<label for="year">Year:</label> ';
        echo '<input type="number" name="year" value="' . esc_attr($year) . '" min="2000" max="' . date('Y') . '"> ';
                // Status (Category) filter
        $cats = get_categories(['taxonomy' => 'category', 'hide_empty' => false]);
        echo '<label for="status">Status:</label> ';
        echo '<select name="status" id="status">';
        $sel_all = (empty($status) || $status === 'all') ? 'selected' : '';
        echo "<option value='all' $sel_all>All</option>";
        if (!is_wp_error($cats)) {
            foreach ($cats as $c) {
                $sel = ($status === $c->slug) ? 'selected' : '';
                echo "<option value='{$c->slug}' $sel>" . esc_html($c->name) . "</option>";
            }
        }
        echo '</select> ';
echo '<input type="submit" class="button" value="Filter">';
        echo '</form>';

        $this->render_table($paged_data);

        // Pagination
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            if ($paged > 1) {
                $prev = add_query_arg(['paged' => $paged - 1, 'year' => $year, 'month' => $month, 'status' => $status]);
                echo '<a class="button" href="' . esc_url($prev) . '">&laquo; Previous</a> ';
            }
            echo ' Page ' . intval($paged) . ' of ' . intval($total_pages) . ' ';
            if ($paged < $total_pages) {
                $next = add_query_arg(['paged' => $paged + 1, 'year' => $year, 'month' => $month, 'status' => $status]);
                echo '<a class="button" href="' . esc_url($next) . '">Next &raquo;</a>';
            }
            echo '</div></div>';
        }

        // Chart (per work order in the month)
        if (!empty($data)) {
            $labels = array_map(function($r) { return $r['work_order_number']; }, $data);
            $billed = array_column($data, 'total_billed');
            $paid   = array_column($data, 'total_paid');
            $balance = array_column($data, 'balance_due');

            $this->render_chart($labels, $billed, $paid, $balance);
        }

        echo '</div>';
    }
}
