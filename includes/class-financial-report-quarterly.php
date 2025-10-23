<?php
if (!defined('ABSPATH')) exit;

class Financial_Report_Quarterly extends Financial_Report_Base {

    public function render_page() {
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $quarter = isset($_GET['quarter']) ? intval($_GET['quarter']) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 25;

        $quarters = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        ];
        $months = $quarters[$quarter] ?? [1, 2, 3];

        $data = $this->get_workorders($year);
        $data = array_filter($data, function($row) use ($months) {
            $m = intval(date('n', strtotime($row['date'])));
            return in_array($m, $months);
        });

        // Filter by status (category)
        if (!empty($status) && $status !== 'all') {
            $data = array_values(array_filter($data, function($row) use ($status) {
                return function_exists('has_term') ? has_term($status, 'category', $row['id']) : true;
            }));
        }
        $total_items = count($data);
        $total_pages = max(1, ceil($total_items / $per_page));
        $offset = ($paged - 1) * $per_page;
        $paged_data = array_slice(array_values($data), $offset, $per_page);

        echo '<div class="wrap"><h1>Quarterly Financial Report (Q' . esc_html($quarter) . ' ' . esc_html($year) . ')</h1>';

        echo '<form method="get" style="margin-bottom:12px">';
        echo '<input type="hidden" name="page" value="financial-reports-quarterly">';
        echo '<label for="year">Year:</label> ';
        echo '<input type="number" name="year" id="year" value="' . esc_attr($year) . '" min="2000" max="' . date('Y') . '"> ';
        echo '<label for="quarter">Quarter:</label> ';
        echo '<select name="quarter" id="quarter">';
        for ($q = 1; $q <= 4; $q++) {
            $sel = ($q == $quarter) ? 'selected' : '';
            echo "<option value='$q' $sel>Q$q</option>";
        }
        echo '</select> ';
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
                $prev = add_query_arg(['paged' => $paged - 1, 'year' => $year, 'quarter' => $quarter, 'status' => $status]);
                echo '<a class="button" href="' . esc_url($prev) . '">&laquo; Previous</a> ';
            }
            echo ' Page ' . intval($paged) . ' of ' . intval($total_pages) . ' ';
            if ($paged < $total_pages) {
                $next = add_query_arg(['paged' => $paged + 1, 'year' => $year, 'quarter' => $quarter, 'status' => $status]);
                echo '<a class="button" href="' . esc_url($next) . '">Next &raquo;</a>';
            }
            echo '</div></div>';
        }

        // Chart (per work order in the quarter)
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
