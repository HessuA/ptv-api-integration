<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2021-11-09 16:04:33
 * @Last Modified by:   Heikki Anttonen
 * @Last Modified time: 2023-11-28 18:56:10
 *
 * @package ptv-api-integration-test
 */

namespace PTV_Api_Integration_Test;

defined( 'ABSPATH' ) || exit;

function prefix_key( $key, $hidden = false ) {
  $prefix = get_prefix();
  return $hidden ? "_{$prefix}_{$key}" : "{$prefix}_{$key}";
} // end prefix_key

function get_api_url() {
  return "https://api.palvelutietovaranto.suomi.fi/api/v11/";
} // end get_api_url

function get_item_post_id_by_api_id( $item_id, $lang ) {
  global $wpdb;

  $return = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE (meta_key = %s AND meta_value = %s) AND post_id IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s)", prefix_key( 'sync_id', true ), $item_id, prefix_key( 'lang', true ), $lang ) );

  return empty( $return ) ? false : $return[0];
} // end get_item_post_id_by_api_id

function get_current_admin_url() {
  return admin_url( sprintf( basename( $_SERVER['REQUEST_URI'] ) ) );
} // end get_current_admin_url
