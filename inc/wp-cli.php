<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2021-11-09 16:08:59
 * @Last Modified by:   Heikki Anttonen
 * @Last Modified time: 2023-11-27 11:05:52
 *
 * @package ptv-api-integration-test
 */

namespace PTV_Api_Integration_Test;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
  \WP_CLI::add_command( str_replace( '_', '-', get_prefix() ), __NAMESPACE__ . '\wp_cli_sync' );
}

function wp_cli_sync( $args, $assoc_args ) {
  $assoc_args = wp_parse_args( $assoc_args, [
    'yes'   => false,
    'force' => false,
  ] );
  
  if ( ! isset( $assoc_args['yes'] ) ) {
    \WP_CLI::confirm( 'Are you sure you want to proceed? Sync might take a while.', $assoc_args );
  }

  $force = false;
  if ( isset( $assoc_args['force'] ) ) {
    $force = true;
  }

  \WP_CLI::log( 'Sync started.' );

  sync( $force );
  
  cleanup_items();

  \WP_CLI::success( 'Sync finished.' );
} // end wp_cli_sync
