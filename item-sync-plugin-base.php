<?php
/**
 * Plugin Name: PTV api integration test
 * Description: Describe what this sync plugin does.
 * Version: 0.5.0
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * @Author: Heikki Anttonen
 * @Date:   2023-03-08 15:23:37
 * @Last Modified by:   Heikki Anttonen
 * @Last Modified time: 2023-11-26 13:34:07
 *
 *
 * @package ptv-api-integration-test
 */

namespace PTV_Api_Integration_Test;

defined( 'ABSPATH' ) || exit;

function get_plugin_version() {
  return 050;
} // end get_plugin_version

function get_prefix() {
  return 'PTV_Api_Integration_Test';
} // end get_prefix

function get_cpt_slug() {
  return 'service';
} // end get_cpt_slug

/**
 * Pure function files.
 */
include plugin_dir_path( __FILE__ ) . '/inc/logging.php';
include plugin_dir_path( __FILE__ ) . '/inc/helpers.php';
include plugin_dir_path( __FILE__ ) . '/inc/request.php';
include plugin_dir_path( __FILE__ ) . '/inc/wp-cli.php';

/**
 * Handlers for singular item sync and clenup.
 */
include plugin_dir_path( __FILE__ ) . '/handlers/item.php';
include plugin_dir_path( __FILE__ ) . '/handlers/item-terms.php';
include plugin_dir_path( __FILE__ ) . '/handlers/cleanup.php';

/**
 * Cron and other automated jobs related functionalities.
 */
include plugin_dir_path( __FILE__ ) . '/inc/cron.php';
register_activation_hook( __FILE__,   __NAMESPACE__ . '\schedule_cron_events' ); // Add cron event for sync on activation
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deschedule_cron_events' );
add_action( 'init',                   __NAMESPACE__ . '\dev_debug_run' ); // Allow running the sync from browser on development
add_action( 'admin_init',             __NAMESPACE__ . '\schedule_cron_events' ); // Ensure cron event is in place
add_action( prefix_key( 'cron' ),     __NAMESPACE__ . '\sync' ); // Cron sync event
add_action( prefix_key( 'cleanup' ),  __NAMESPACE__ . '\cleanup' ); // Cron cleanup event

/**
 * Admin side functionalities.
 */
include plugin_dir_path( __FILE__ ) . '/admin/notices.php';
add_action( 'current_screen', __NAMESPACE__ . '\maybe_show_update_notice' );

include plugin_dir_path( __FILE__ ) . '/admin/manual-sync.php';
add_action( 'admin_menu', __NAMESPACE__ . '\add_manual_sync_menu_item' );

// Allow manual sync
add_filter( prefix_key( 'manual_sync_allow' ), '__return_true' );
