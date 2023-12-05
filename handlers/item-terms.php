<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2023-01-25 14:23:51
 * @Last Modified by:   Heikki Anttonen
 * @Last Modified time: 2023-11-28 12:35:37
 *
 * @package ptv-api-integration-test
 */

namespace PTV_Api_Integration_Test;

defined( 'ABSPATH' ) || exit;

function save_item_terms( $item ) {

  $taxonomy_classes = 'service_classes';
  $taxonomy_tags    = 'service_tags';

  $term_classes_ids = get_term_ids_from_service( $item['serviceClasses'], $taxonomy_classes );
  $term_tag_ids     = get_term_ids_from_service( $item['ontologyTerms'], $taxonomy_tags );
  
  $taxomies = [
    $taxonomy_classes => $term_classes_ids,
    $taxonomy_tags    => $term_tag_ids,
  ];

  // set terms
  if ( ! empty( $taxomies ) ) {
    foreach ( $taxomies as $key => $terms ) {
      wp_set_post_terms( $item['wp_post_id'], $terms, $key);
    }
  }
} // end save_item_terms

function maybe_save_tax_term( $term = null, $taxonomy = null, $metadata = [] ) {
  if ( empty( $term ) || empty( $taxonomy ) ) {
    return false; // Bail early if there's no term.
  }

  // Check if term exists in wp. If does, return term id.
  $term_exists = term_exists( $term, $taxonomy );
  if ( ! empty( $term_exists ) ) {
    return $term_exists['term_id'];
  }

  // Term didn't exist, try to insert it into wp.
  $insert_term = wp_insert_term( $term, $taxonomy );
  if ( is_wp_error( $insert_term ) ) {
    // Term insert failed, log it and bail.
    log( "Failed saving term {$term} to {$taxonomy}", 'debug', $insert_term );
    return false;
  }

  if ( ! empty( $metadata ) ) {
    foreach ( $metadata as $key => $value ) {
      update_term_meta( $insert_term['term_id'], $key, $value );
    }
  }
  return $insert_term['term_id'];
} // end maybe_save_tax_term

/**
 * Get all terms from service
 * 
 * @param array $term_array Array where the terms can be found
 * @param string $taxonomy Taxonomy name
 */
function get_term_ids_from_service( $term_array, $taxonomy ) {

  // Bail early if there's no array
  if ( ! isset( $term_array ) ) {
    return;
  }

  $term_ids = [];

  foreach ( $term_array as $term ) {

    if ( ! isset( $term['uri'] ) ) {
      continue;
    }

    $term_uri = $term['uri'];

    $translations = [];

    foreach ( $term['name'] as $term_name ) {

      if ( ! isset( $term_name['value'] ) || ! isset( $term_name['language'] ) ) {
        continue;
      }

      $term_id = maybe_save_tax_term( $term_name['value'], $taxonomy, [
        prefix_key( 'uri' ) => $term_uri
      ] );

      if ( function_exists( 'pll_set_term_language' ) ) {
        pll_set_term_language( $term_id, $term_name['language'] );
      }

      $translations[$term_name['language']] =  $term_id;
      $term_ids[] = $term_id; 

    }
    
    if ( function_exists( 'pll_save_term_translations' ) ) {
      pll_save_term_translations( $translations );
    }

  }
  return $term_ids;
}
