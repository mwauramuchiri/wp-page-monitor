<?php
/**
 * Admin page template for WP Page Monitor
 * 
 * @package WP_Page_Monitor
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap">
    <h1>WP Page Monitor</h1>
    <p>Use this tool to monitor WordPress hooks and actions on any page.</p>
    
    <div class="wp-page-monitor-controls">
        <a href="<?php echo esc_url(add_query_arg('wp_page_monitor', '1')); ?>" class="button button-primary">
            Monitor Current Page
        </a>
    </div>

    <?php if ($this->is_monitoring && !empty($this->hooks_log)): ?>
        <div class="wp-page-monitor-results">
            <h2>Hooks Log</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Hook Name</th>
                        <th>Type</th>
                        <th>Execution Time</th>
                        <th>Caller</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Sort hooks by execution time
                    usort($this->hooks_log, function($a, $b) {
                        return $b['execution_time'] <=> $a['execution_time'];
                    });
                    
                    foreach ($this->hooks_log as $log): 
                        // Format execution time for display
                        $execution_time = number_format($log['execution_time'] * 1000, 2); // Convert to milliseconds
                        $time_class = $log['execution_time'] > 0.1 ? 'slow-hook' : ''; // Highlight slow hooks
                    ?>
                        <tr class="<?php echo esc_attr($time_class); ?>">
                            <td><?php echo esc_html($log['hook']); ?></td>
                            <td><?php echo esc_html($log['type']); ?></td>
                            <td><?php echo esc_html($execution_time); ?> ms</td>
                            <td>
                                <?php
                                $caller = $log['caller'];
                                echo esc_html(sprintf(
                                    '%s%s%s (line %d)',
                                    !empty($caller['class']) ? $caller['class'] . '::' : '',
                                    $caller['function'],
                                    !empty($caller['file']) ? ' in ' . basename($caller['file']) : '',
                                    $caller['line']
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div> 