<?php
if (!defined('ABSPATH')) exit;

class Financial_Report_Yearly extends Financial_Report_Base {

    public function render_page() {
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

        // Fetch ALL work orders for the year (no pagination)
        $data = $this->get_workorders($year);

        echo '<div class="wrap"><h1>Yearly Financial Report (' . esc_html($year) . ')</h1>';

        // Filters
        echo '<form method="get" style="margin-bottom:12px">';
        echo '<input type="hidden" name="page" value="financial-reports">';
        echo '<label for="year">Year:</label> ';
        echo '<input type="number" name="year" value="' . esc_attr($year) . '" min="2000" max="' . date('Y') . '"> ';
        echo '<input type="submit" class="button" value="Filter">';
        echo '</form>';

        // Full table (all rows)
        $this->render_table($data);

        // Chart (monthly aggregates)
        if (!empty($data)) {
            $months = [];
            $billed = $paid = $balance = [];
            foreach (range(1, 12) as $m) {
                $month_data = array_filter($data, function($row) use ($m) {
                    return intval(date('n', strtotime($row['date']))) === $m;
                });
                $months[]  = date('M', mktime(0,0,0,$m,1));
                $billed[]  = array_sum(array_column($month_data, 'total_billed'));
                $paid[]    = array_sum(array_column($month_data, 'total_paid'));
                $balance[] = array_sum(array_column($month_data, 'balance_due'));
            }
            $this->render_chart($months, $billed, $paid, $balance);
        }

        echo '</div>';
    }
}
