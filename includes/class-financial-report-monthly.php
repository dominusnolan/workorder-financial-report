<?php
if (!defined('ABSPATH')) exit;

class Financial_Report_Monthly extends Financial_Report_Base {

    public function render_page() {
        $year  = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

        // Fetch ALL work orders for the selected month (no pagination)
        $data  = $this->get_workorders($year, $month);

        echo '<div class="wrap"><h1>Monthly Financial Report (' . esc_html(date('F', mktime(0,0,0,$month,1))) . ' ' . esc_html($year) . ')</h1>';

        // Filters
        echo '<form method="get" style="margin-bottom:12px">';
        echo '<input type="hidden" name="page" value="financial-reports-monthly">';
        echo '<label for="month">Month:</label> ';
        echo '<select name="month" id="month">';
        for ($m = 1; $m <= 12; $m++) {
            $sel = ($m == $month) ? 'selected' : '';
            echo "<option value=\"$m\" $sel>" . date('F', mktime(0,0,0,$m,1)) . "</option>";
        }
        echo '</select> ';
        echo '<label for="year">Year:</label> ';
        echo '<input type="number" name="year" value="' . esc_attr($year) . '" min="2000" max="' . date('Y') . '"> ';
        echo '<input type="submit" class="button" value="Filter">';
        echo '</form>';

        // Full table (all rows)
        $this->render_table($data);

        // Chart (per work order in the month)
        if (!empty($data)) {
            $labels  = array_map(function($r) { return $r['work_order_number']; }, $data);
            $billed  = array_column($data, 'total_billed');
            $paid    = array_column($data, 'total_paid');
            $balance = array_column($data, 'balance_due');
            $this->render_chart($labels, $billed, $paid, $balance);
        }

        echo '</div>';
    }
}
