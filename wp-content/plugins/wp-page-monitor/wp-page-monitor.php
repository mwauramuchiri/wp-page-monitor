<?php
/**
 * Plugin Name: WP Page Monitor
 * Plugin URI: https://github.com/mwauramuchiri/wp-page-monitor
 * Description: A debugging tool that monitors and logs WordPress hooks and actions when a page is loaded.
 * Version: 1.0.0
 * Author: MWAURA MUCHIRI
 * Author URI: https://mwauramuchiri.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-page-monitor
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WP_PAGE_MONITOR_VERSION', '1.0.0');
define('WP_PAGE_MONITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_PAGE_MONITOR_PLUGIN_URL', plugin_dir_url(__FILE__));

class WP_Page_Monitor {
    /**
     * Singleton instance of the class
     * @var WP_Page_Monitor|null
     */
    private static $instance = null;

    /**
     * Array to store the logged hooks and their information
     * @var array
     */
    private $hooks_log = array();

    /**
     * Flag to track if monitoring is currently active
     * @var bool
     */
    private $is_monitoring = false;

    /**
     * Array to store hook start times
     * @var array
     */
    private $hook_start_times = array();

    /**
     * Get the singleton instance of the class
     * Implements the Singleton pattern to ensure only one instance exists
     * 
     * @return WP_Page_Monitor
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - sets up the initial hooks for the plugin
     * Private to enforce singleton pattern
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Initialize the plugin
     * Checks if monitoring should be started based on URL parameter
     * Only allows administrators to start monitoring
     */
    public function init() {
        if (isset($_GET['wp_page_monitor']) && current_user_can('manage_options')) {
            $this->start_monitoring();
        }
    }

    /**
     * Add the plugin's menu item to the WordPress admin menu
     * Places it under the Tools menu
     */
    public function add_admin_menu() {
        add_management_page(
            'WP Page Monitor',
            'Page Monitor',
            'manage_options',
            'wp-page-monitor',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue necessary CSS and JavaScript files for the admin interface
     * Only loads on the plugin's admin page
     * 
     * @param string $hook The current admin page
     */
    public function enqueue_admin_assets($hook) {
        if ('tools_page_wp-page-monitor' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wp-page-monitor-admin',
            WP_PAGE_MONITOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_PAGE_MONITOR_VERSION
        );

        wp_enqueue_script(
            'wp-page-monitor-admin',
            WP_PAGE_MONITOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WP_PAGE_MONITOR_VERSION,
            true
        );
    }

    /**
     * Start monitoring WordPress hooks and actions
     * Hooks into all registered actions and filters
     * Only starts if not already monitoring
     */
    private function start_monitoring() {
        if ($this->is_monitoring) {
            return;
        }

        $this->is_monitoring = true;
        $this->hooks_log = array();
        $this->hook_start_times = array();

        // Hook into all actions and filters
        global $wp_filter;
        foreach ($wp_filter as $hook_name => $hook_obj) {
            // Add start time tracking
            add_action($hook_name, function() use ($hook_name) {
                $this->hook_start_times[$hook_name] = microtime(true);
            }, -9999);

            // Add end time tracking and logging
            add_action($hook_name, function() use ($hook_name) {
                $this->log_hook($hook_name, 'action');
            }, 9999);

            // Add filter start time tracking
            add_filter($hook_name, function($value) use ($hook_name) {
                $this->hook_start_times[$hook_name] = microtime(true);
                return $value;
            }, -9999);

            // Add filter end time tracking and logging
            add_filter($hook_name, function($value) use ($hook_name) {
                $this->log_hook($hook_name, 'filter');
                return $value;
            }, 9999);
        }
    }

    /**
     * Log information about a hook when it's triggered
     * Captures hook name, type, execution time, and caller information
     * 
     * @param string $hook_name The name of the hook being triggered
     * @param string $type The type of hook ('action' or 'filter')
     */
    private function log_hook($hook_name, $type) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]) ? $backtrace[1] : array();
        
        // Calculate execution time
        $end_time = microtime(true);
        $start_time = isset($this->hook_start_times[$hook_name]) ? $this->hook_start_times[$hook_name] : $end_time;
        $execution_time = $end_time - $start_time;
        
        $this->hooks_log[] = array(
            'hook' => $hook_name,
            'type' => $type,
            'time' => $end_time,
            'execution_time' => $execution_time,
            'caller' => array(
                'function' => isset($caller['function']) ? $caller['function'] : '',
                'class' => isset($caller['class']) ? $caller['class'] : '',
                'file' => isset($caller['file']) ? $caller['file'] : '',
                'line' => isset($caller['line']) ? $caller['line'] : '',
            )
        );
    }

    /**
     * Load a template file
     * 
     * @param string $template_name The name of the template file to load
     * @return void
     */
    private function load_template($template_name) {
        $template_path = WP_PAGE_MONITOR_PLUGIN_DIR . 'templates/' . $template_name;
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            wp_die(sprintf(
                __('Template file not found: %s', 'wp-page-monitor'),
                $template_path
            ));
        }
    }

    /**
     * Render the admin page interface
     * Displays the monitoring controls and results table
     * Shows hook information in a formatted table if monitoring is active
     */
    public function render_admin_page() {
        $this->load_template('admin-page.php');
    }
}

// Initialize the plugin
function wp_page_monitor_init() {
    WP_Page_Monitor::get_instance();
}
add_action('plugins_loaded', 'wp_page_monitor_init'); 