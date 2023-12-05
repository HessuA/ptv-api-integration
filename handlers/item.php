<?php
/**
 * @Author: Timi Wahalahti
 * @Date:   2021-11-09 16:22:00
 * @Last Modified by:   Heikki Anttonen
 * @Last Modified time: 2023-12-03 03:16:45
 *
 * @package ptv-api-integration-test
 */

namespace PTV_Api_Integration_Test;

defined( 'ABSPATH' ) || exit;

function save_item( $item, $force ) {
  $data_hash_key = prefix_key( 'data_hash', true );

  if ( ! isset( $item['id'] ) ) {
    return;
  }

  log( "Updating item API ID: {$item['id']}", 'debug' );
  // log( "Item: ", 'debug', $item );

  // Get available languages
  if ( function_exists( 'pll_languages_list' ) ) {
    $languages = pll_languages_list();
  }

  if ( ! isset( $languages ) ) {
    log( "Languages not found", 'debug' );
    return;
  }

  // Polylang translations sync array
  $translations = [];

  foreach ( $languages as $lang ) {

    // Try to get WP post ID matching this item
    $item_post_id = get_item_post_id_by_api_id( $item['id'], $lang );
  
    if ( $item_post_id ) {
      log( "Item WP ID is {$item_post_id}", 'debug' );
    } else {
      log( 'Item WP ID not found', 'debug' );
    }

    $data_hash = md5( json_encode( $item ) );

    /**
     * If item exists already in databse, check the new data
     * hash againts stored one to check if anything has changed.
     * In case hashes are same, we can safely assume that data
     * has not changed and skip the save process of this item.
     */
    if ( $item_post_id && ! $force ) {
      $data_hash_old = get_post_meta( $item_post_id, $data_hash_key, true );

      if ( $data_hash === $data_hash_old ) {
        update_post_meta( $item_post_id, prefix_key( 'sync_time', true ), wp_date( 'Y-m-d H:i:s' ) );
        log( 'Item skipped. New and old data hash matches, assuming no data changes', 'debug' );
        return;
      }
    }
  
    $title = '';
    // set the title
    if ( isset( $item['serviceNames'] ) ) {
      foreach ( $item['serviceNames'] as $name ) {
        if ( $lang === $name['language'] && 'Name' === $name['type'] ) {
          $title = $name['value'];
        }
      }
    }

    if ( empty( $title ) ) {
      continue;
    }

    $save = [
      'ID'            => $item_post_id,
      'post_type'     => get_cpt_slug(),
      'post_status'   => 'publish',
      'post_title'    => $title,
      'meta_input'    => [
        prefix_key( 'sync_id', true )         => $item['id'],
        prefix_key( 'sync_time', true )       => wp_date( 'Y-m-d H:i:s' ),
        prefix_key( 'updated_time', true )    => wp_date( 'Y-m-d H:i:s' ),
        prefix_key( 'data_hash_base', true )  => $item,
        $data_hash_key                        => $data_hash,
      ],
    ];

    /**
     * Consider disabling Simple History logging during the item save if
     * the sync runs often and there are hundereds of items being synced.
     * Simple History logging might end up increasing your database size.
     */
    // add_filter( 'simple_history/log/do_log', '__return_false' );

    // Save post
    $insert = wp_insert_post( $save );

    // Set post language
    if ( function_exists( 'pll_set_post_language' ) ) {
      pll_set_post_language( $insert, $lang );
    }

    // Set id to translation sync array
    $translations[$lang] = $insert;

    // Set language to post meta
    update_post_meta( $insert, prefix_key( 'lang', true ), $lang );

    // Get data into custom fields
    // Descriptions
    $item['summary'] = parse_api_descriptions_data_to_acf_field( $item, 'serviceDescriptions', 'value', 'Summary', $lang );
    $item['description'] = parse_api_descriptions_data_to_acf_field( $item, 'serviceDescriptions', 'value', 'Description', $lang );
    $item['userInstruction'] = parse_api_descriptions_data_to_acf_field( $item, 'serviceDescriptions', 'value', 'UserInstruction', $lang );

    // Organizations
    if ( isset( $item['organizations'] ) ) {
      foreach ( $item['organizations'] as $organization ) {
        if ( 'Responsible' !== $organization['roleType'] ) {
         continue; 
        }
        $item['organization'] = $organization['organization']['name'];
      }
    }

    // ACF fields
    $fillable = [
      'field_65634603fb781' => 'summary',
      'field_6563461ffb782' => 'description',
      'field_65634668fd7e2' => 'userInstruction',
      'field_65649eb1b6789' => 'organization',
    ];

    // Save/update acf fields
    foreach ( $fillable as $key => $name ) {
      update_field( $key, $item[$name], $insert );
    }

    // add_filter( 'simple_history/log/do_log', '__return_true' );

    if ( $insert ) {
      $item['wp_post_id'] = $insert; // Add WP ID to item details

      if ( $item_post_id ) {
        log( 'Item updated', 'debug' );
      } else {
        log( "New item saved with WP ID {$insert}", 'debug' );
      }
    } else {
      log( 'Item save failed', 'error' );
      return;
    }

    save_item_terms( $item );
    }

    // Sync post translations
    if ( function_exists( 'pll_save_post_translations' ) ) {
      pll_save_post_translations( $translations );

  }
}  // end save_item


/**
 * Parse api data to acf field
 * 
 * @param array $item Api item
 * @param string $item_key Array key
 * @param string $field_key Value field key
 * @param string $type Field type
 * @param string $language Language
 * 
 * @return string
 */
function parse_api_descriptions_data_to_acf_field($item, $item_key, $field_key, $type = '', $language = 'fi' ) {
  if ( ! empty( $item[$item_key] && is_array( $item[$item_key] ) ) ) {
    foreach ( $item[$item_key] as $v ) {
      if ( isset( $v[$field_key] ) && $language === $v['language'] && empty( $type ) ) {
        return $v[$field_key];
        break;
      } else if ( isset( $v[$field_key] ) && $language === $v['language'] && $type === $v['type'] ) {
        return $v[$field_key];
        break;
      }
    }
  } else {
    return '';
  }
}
