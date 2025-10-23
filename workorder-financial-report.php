<?php
/**
 * Plugin Name: Workorder Financial Report
 * Description: Displays monthly, quarterly, and yearly financial reports for Workorders CPT with Chart.js visualization.
 * Author: Dominus Financial Report
 */

if (!defined('ABSPATH')) exit;

define('WFR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WFR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WFR_PLUGIN_DIR . 'includes/class-financial-report-base.php';
require_once WFR_PLUGIN_DIR . 'includes/class-financial-report-yearly.php';
require_once WFR_PLUGIN_DIR . 'includes/class-financial-report-monthly.php';
require_once WFR_PLUGIN_DIR . 'includes/class-financial-report-quarterly.php';

class Workorder_Financial_Report {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function register_menus() {
        add_menu_page(
            'Financial Reports',
            'Financial Reports',
            'manage_options',
            'financial-reports',
            [new Financial_Report_Yearly(), 'render_page'],
            'dashicons-chart-bar',
            6
        );

        // Rename top-level to "Yearly Report" so submenu list looks clean
        global $submenu;
        add_action('admin_menu', function() {
            global $submenu;
            if (isset($submenu['financial-reports'][0])) {
                $submenu['financial-reports'][0][0] = 'Yearly Report';
            }
        });

        add_submenu_page(
            'financial-reports',
            'Quarterly Report',
            'Quarterly Report',
            'manage_options',
            'financial-reports-quarterly',
            [new Financial_Report_Quarterly(), 'render_page']
        );

        add_submenu_page(
            'financial-reports',
            'Monthly Report',
            'Monthly Report',
            'manage_options',
            'financial-reports-monthly',
            [new Financial_Report_Monthly(), 'render_page']
        );
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'financial-reports') === false) return;

        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        // Simple inline CSS for chart containers
        add_action('admin_print_footer_scripts', function() {
            echo '<style>.chart-container{width:100%;max-width:900px;margin-top:20px}</style>';
        });
    }
}

new Workorder_Financial_Report();
