jQuery(document).ready(function($) {
    // Add any future JavaScript functionality here
    $('.wp-page-monitor-results table').on('click', 'tr', function() {
        $(this).toggleClass('selected');
    });
}); 