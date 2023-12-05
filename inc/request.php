<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2021-11-09 16:08:32
 * @Last Modified by:   Heikki Anttonen
 * @Last Modified time: 2023-11-26 13:44:07
 *
 * @package ptv-api-integration-test
 */

namespace PTV_Api_Integration_Test;

defined( 'ABSPATH' ) || exit;

function call_api( $url_suffix, $params = [], $args = [] ) {
  $api_url = get_api_url() . $url_suffix;

  if ( ! empty( $params ) ) {
    $api_url = add_query_arg( $params, $api_url );
  }

  $args = wp_parse_args( $args, [] );

  log( "API called {$api_url}", 'debug' );

  $response = wp_remote_request( $api_url, $args );

  $response_code = wp_remote_retrieve_response_code( $response );
  if ( 200 !== $response_code ) {
    log( "API returned error code {$response_code}", 'error' );
    return false;
  }

  $body = wp_remote_retrieve_body( $response );

  if ( empty( $body ) ) {
    log( 'API returned empty body', 'debug' );
    return false;
  }

  $data = json_decode( $body, true );

  return $data;
} // end call_api
