<?php

class WM_Forms_Validate
{
  public static function init()
  {
    add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
    add_action( 'wp_ajax_wm_form', array( __CLASS__, 'ajax' ) );
    add_action( 'wp_ajax_nopriv_wm_form', array( __CLASS__, 'ajax' ) );
  }

  public static function enqueue_scripts()
  {
    wp_enqueue_script( 'jquery-validate', plugins_url( 'js/jquery.validate.min.js' , __FILE__ ), array( 'jquery' ), null, true );
    wp_enqueue_script( 'wm-forms-validate', plugins_url( 'js/wm-forms-validate.js' , __FILE__ ), array( 'jquery-validate', 'jquery-serialize-object' ), null, true );
    wp_localize_script( 'wm-forms-validate', 'ajaxUrl', admin_url( 'admin-ajax.php' ) );
  }

  public static function ajax()
  {
    $post = get_post( $_POST['wm_form_id'] );
    if ( ! wp_verify_nonce( $_POST[$post->post_name . '_nonce'], $post->post_name ) ) {
      wp_die( __( 'Security verification failed.', 'wm_forms' ) );
    }
    $fields = wm_get_form_fields( $post->ID );
    $results = array();
    foreach ( $fields as $name => $field ) {
      $key = sanitize_key( $field['fid'] );
      switch ( $field['type'] )
      {
        case 'checkbox':
        $results[$key] = ! empty( $_POST[$name] );
        break;

        case 'radio':
        case 'select':
        $results[$key] = sanitize_key( $_POST[$name] );
        break;

        case 'email':
        $results[$key] = sanitize_email( $_POST[$name] );
        break;

        default:
        $results[$key] = sanitize_text_field( $_POST[$name] );
        break;
      }
    }
    if ( $results ) {
      global $wpdb;
      if ( $wpdb->insert( $wpdb->prefix . 'form_results', array(
        'form_id' => $post->ID,
        'value' => json_encode($results)
      ) ) ) {
        wp_send_json( $wpdb->insert_id );
      } else {
        wp_send_json( 'Error.' );
      }
    }
  }
}
add_action( 'init', array( WM_Forms_Validate, 'init' ) );
