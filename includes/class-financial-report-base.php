<?php
if (!defined('ABSPATH')) exit;

class Financial_Report_Base {

    protected function get_workorders($year = null, $month = null) {
        $args = [
            'post_type'      => 'workorder',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];

        if ($year || $month) {
            $date_query = [];
            if ($year) $date_query['year'] = $year;
            if ($month) $date_query['month'] = $month;
            $args['date_query'] = [$date_query];
        }

        $query = new WP_Query($args);
        $data = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();

                $labor_cost = $travel_cost = $other_cost = 0;

                // ACF repeater: wo_invoice -> activity, amount
                if (function_exists('have_rows') && have_rows('wo_invoice', $id)) {
                    while (have_rows('wo_invoice', $id)) {
                        the_row();
                        $type   = get_sub_field('activity');
                        $amount = floatval(get_sub_field('amount'));
                        if ($type === 'Labor')  { $labor_cost  += $amount; }
                        elseif ($type === 'Travel') { $travel_cost += $amount; }
                        elseif ($type === 'Other')  { $other_cost  += $amount; }
                    }
                }

                $data[] = [
                    'id'                => $id,
                    'title'             => get_the_title(),
                    'work_order_number' => function_exists('get_field') ? get_field('work_order_number', $id) : '',
                    'purchase_order'    => function_exists('get_field') ? get_field('_dq_purchase_order', $id) : '',
                    'total_billed'      => floatval(function_exists('get_field') ? get_field('wo_total_billed', $id) : 0),
                    'total_paid'        => floatval(function_exists('get_field') ? get_field('wo_total_paid', $id) : 0),
                    'balance_due'       => floatval(function_exists('get_field') ? get_field('wo_balance_due', $id) : 0),
                    'labor_cost'        => $labor_cost,
                    'travel_cost'       => $travel_cost,
                    'other_cost'        => $other_cost,
                    'date'              => get_the_date('Y-m-d', $id)
                ];
            }
            wp_reset_postdata();
        }

        return $data;
    }

    protected function render_table($data) {
        if (empty($data)) {
            echo '<p>No data found for this period.</p>';
            return;
        }

        $total_billed = array_sum(array_column($data, 'total_billed'));
        $total_paid   = array_sum(array_column($data, 'total_paid'));
        $balance_due  = array_sum(array_column($data, 'balance_due'));
        $total_labor  = array_sum(array_column($data, 'labor_cost'));
        $total_travel = array_sum(array_column($data, 'travel_cost'));
        $total_other  = array_sum(array_column($data, 'other_cost'));

        echo '<table class="widefat striped">';
        echo '<thead><tr>
                <th>Work Order #</th>
                <th>Purchase Order</th>
                <th>Total Billed</th>
                <th>Total Paid</th>
                <th>Balance Due</th>
                <th>Total Labor Cost</th>
                <th>Total Travel Cost</th>
                <th>Total Other Expenses</th>
                <th>Date</th>
            </tr></thead><tbody>';

        foreach ($data as $row) {
            echo '<tr>
                    <td>' . esc_html($row['work_order_number']) . '</td>
                    <td>' . esc_html($row['purchase_order']) . '</td>
                    <td>$' . number_format($row['total_billed'], 2) . '</td>
                    <td>$' . number_format($row['total_paid'], 2) . '</td>
                    <td>$' . number_format($row['balance_due'], 2) . '</td>
                    <td>$' . number_format($row['labor_cost'], 2) . '</td>
                    <td>$' . number_format($row['travel_cost'], 2) . '</td>
                    <td>$' . number_format($row['other_cost'], 2) . '</td>
                    <td>' . esc_html($row['date']) . '</td>
                </tr>';
        }

        echo '</tbody></table>';

        echo '<h3>Totals</h3>';
        echo '<ul>
                <li><strong>Total Billed:</strong> $' . number_format($total_billed, 2) . '</li>
                <li><strong>Total Paid:</strong> $' . number_format($total_paid, 2) . '</li>
                <li><strong>Balance Due:</strong> $' . number_format($balance_due, 2) . '</li>
                <li><strong>Total Labor Cost:</strong> $' . number_format($total_labor, 2) . '</li>
                <li><strong>Total Travel Cost:</strong> $' . number_format($total_travel, 2) . '</li>
                <li><strong>Total Other Expenses:</strong> $' . number_format($total_other, 2) . '</li>
            </ul>';
    }

    protected function render_chart($labels, $billed, $paid, $balance) {
        $chart_id = 'chart_' . wp_generate_uuid4();
        ?>
        <div class="chart-container">
            <canvas id="<?php echo esc_attr($chart_id); ?>"></canvas>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('<?php echo esc_js($chart_id); ?>').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo wp_json_encode($labels); ?>,
                    datasets: [
                        { label: 'Total Billed', data: <?php echo wp_json_encode($billed); ?> },
                        { label: 'Total Paid', data: <?php echo wp_json_encode($paid); ?> },
                        { label: 'Balance Due', data: <?php echo wp_json_encode($balance); ?> }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
        </script>
        <?php
    }
}
