<?php
if (!defined('ABSPATH')) exit;

class Financial_Report_Quarterly extends Financial_Report_Base {

    public function render_page() {
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $quarter = isset($_GET['quarter']) ? intval($_GET['quarter']) : 1;

        $quarters = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        ];
        $months = $quarters[$quarter] ?? [1, 2, 3];

        // Fetch ALL work orders for the quarter (no pagination)
        $data = [];
        foreach ($months as $m) {
            $data = array_merge($data, $this->get_workorders($year, $m));
        }

        echo '<div class="wrap"><h1>Quarterly Financial Report (Q' . esc_html($quarter) . ' ' . esc_html($year) . ')</h1>';

        // Filters
        echo '<form method="get" style="margin-bottom:12px">';
        echo '<input type="hidden" name="page" value="financial-reports-quarterly">';
        echo '<label for="quarter">Quarter:</label> ';
        echo '<select name="quarter" id="quarter">';
        foreach ([1,2,3,4] as $q) {
            $sel = ($q == $quarter) ? 'selected' : '';
            echo "<option value=\"$q\" $sel>Q$q</option>";
        }
        echo '</select> ';
        echo '<label for="year">Year:</label> ';
        echo '<input type="number" name="year" value="' . esc_attr($year) . '" min="2000" max="' . date('Y') . '"> ';
        echo '<input type="submit" class="button" value="Filter">';
        echo '</form>';

        // Full table (all rows)
        $this->render_table($data);

        // Chart (per work order in the quarter)
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
