<?php
if (!defined('ABSPATH')) exit;

class Financial_Report_Yearly extends Financial_Report_Base {

    public function render_page() {
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 25;

        $data = $this->get_workorders($year);
        $total_items = count($data);
        $total_pages = max(1, ceil($total_items / $per_page));
        $offset = ($paged - 1) * $per_page;

        $paged_data = array_slice($data, $offset, $per_page);

        echo '<div class="wrap"><h1>Yearly Financial Report (' . esc_html($year) . ')</h1>';

        echo '<form method="get" style="margin-bottom:12px">';
        echo '<input type="hidden" name="page" value="financial-reports">';
        echo '<label for="year">Select Year:</label> ';
        echo '<input type="number" name="year" value="' . esc_attr($year) . '" min="2000" max="' . date('Y') . '"> ';
        echo '<input type="submit" class="button" value="Filter">';
        echo '</form>';

        $this->render_table($paged_data);

        // Pagination
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            if ($paged > 1) {
                $prev = add_query_arg(['paged' => $paged - 1, 'year' => $year]);
                echo '<a class="button" href="' . esc_url($prev) . '">&laquo; Previous</a> ';
            }
            echo ' Page ' . intval($paged) . ' of ' . intval($total_pages) . ' ';
            if ($paged < $total_pages) {
                $next = add_query_arg(['paged' => $paged + 1, 'year' => $year]);
                echo '<a class="button" href="' . esc_url($next) . '">Next &raquo;</a>';
            }
            echo '</div></div>';
        }

        // Chart (monthly aggregates)
        if (!empty($data)) {
            $months = [];
            $billed = $paid = $balance = [];

            foreach (range(1, 12) as $m) {
                $month_data = array_filter($data, function($row) use ($m) {
                    return intval(date('n', strtotime($row['date']))) === $m;
                });
                $months[] = date('M', mktime(0,0,0,$m,1));
                $billed[] = array_sum(array_column($month_data, 'total_billed'));
                $paid[] = array_sum(array_column($month_data, 'total_paid'));
                $balance[] = array_sum(array_column($month_data, 'balance_due'));
            }

            $this->render_chart($months, $billed, $paid, $balance);
        }

        echo '</div>';
    }
}
