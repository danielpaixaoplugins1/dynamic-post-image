<?php
/*
Plugin Name: Dynamic Rotating Posts
Description: Display rotating posts from a specific category with configurable time interval via shortcode.
Version: 1.0
Author: Seu Nome
*/

// Enqueue JavaScript
function rotating_posts_enqueue_script() {
    wp_enqueue_script('rotating-posts-js', plugins_url('/rotating-posts-plugin.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('rotating-posts-js', 'ajax_params', array('ajax_url' => admin_url('admin-ajax.php'), 'interval' => get_option('rotating_posts_interval', 5000)));
}
add_action('wp_enqueue_scripts', 'rotating_posts_enqueue_script');

// Shortcode
function rotating_posts_shortcode() {
    return '<div id="rotating-posts-container">Carregando postagens...</div>';
}
add_shortcode('rotating_posts', 'rotating_posts_shortcode');

// AJAX handler
function fetch_rotating_posts() {
    $args = array(
        'posts_per_page' => 1,
        'orderby' => 'rand',
        'category_name' => 'mensagem'
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            echo '<h2>' . get_the_title() . '</h2>';
            echo '<div>' . get_the_excerpt() . '</div>';
        }
    }
    wp_reset_postdata();
    die();
}
add_action('wp_ajax_nopriv_fetch_rotating_posts', 'fetch_rotating_posts');
add_action('wp_ajax_fetch_rotating_posts', 'fetch_rotating_posts');

// Admin settings
function rotating_posts_settings_init() {
    add_settings_section('rotating_posts_settings_section', 'Rotating Posts Settings', 'rotating_posts_settings_section_cb', 'reading');
    add_settings_field('rotating_posts_interval', 'Interval for Rotating Posts (ms)', 'rotating_posts_interval_cb', 'reading', 'rotating_posts_settings_section');
    register_setting('reading', 'rotating_posts_interval');
}
add_action('admin_init', 'rotating_posts_settings_init');

function rotating_posts_settings_section_cb() {
    echo '<p>Set the time interval for rotating posts. Enter time in milliseconds (e.g., 5000 for 5 seconds).</p>';
}

function rotating_posts_interval_cb() {
    $interval = get_option('rotating_posts_interval', 5000);
    echo "<input type='text' name='rotating_posts_interval' value='" . esc_attr($interval) . "' />";
}

// JavaScript
file_put_contents(plugin_dir_path(__FILE__) . 'rotating-posts-plugin.js', <<<EOD
jQuery(document).ready(function($) {
    function updatePosts() {
        $.ajax({
            url: ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_rotating_posts'
            },
            success: function(response) {
                $('#rotating-posts-container').html(response);
            }
        });
    }
    updatePosts();
    setInterval(updatePosts, ajax_params.interval); // Atualiza conforme o intervalo definido
});
EOD
);
