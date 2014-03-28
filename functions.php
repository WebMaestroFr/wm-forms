<?php

function get_forms( $args = array() ) {
  return get_posts( array_merge( $args, array( 'post_type' => 'form' ) ) );
}

function get_form_fields( $form_id ) {
  $form = get_post( $form_id );
  $meta = json_decode( get_post_meta( $form_id, 'form_fields', true ), true );
  $fields = array();
  foreach ( $meta as $field ) {
    $name = sanitize_key( "f_{$field['fid']}" );
    $fields[$name] = $field;
  }
  return $fields;
}

function get_form_results( $form_id, $html = true ) {
  $results = get_post_meta( $form_id, 'form_results' );
  $fields = get_form_fields( $form_id );
  foreach ( $results as $name => $result ) {
    if ( !isset( $result['result_id'] ) ) {
      // TODO : REMOVE AFTER UPDATE
      $prev = $result;
      $result['result_id'] = uniqid();
      update_post_meta( $form_id, 'form_results', $result, $prev );
    }
    $results[$name] = WM_Form_Results::parse( $fields, $result, $html );
  }
  return array_reverse( $results );
}
