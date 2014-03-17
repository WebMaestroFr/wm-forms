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
    if ( 'form' === get_post_type() ) {
      wp_register_script( 'jquery-validate', plugins_url( 'js/jquery.validate.min.js' , __FILE__ ), array( 'jquery' ), null, true );
      wp_enqueue_script( 'wm-forms-validate', plugins_url( 'js/wm-forms-validate.js' , __FILE__ ), array( 'jquery-validate', 'jquery-serialize-object' ), null, true );
      wp_localize_script( 'wm-forms-validate', 'ajax', array(
        'url' => admin_url( 'admin-ajax.php' ),
        'spinner' => admin_url( 'images/spinner.gif')
      ) );
    }
  }

  public static function ajax()
  {
    $form = get_post( $_POST['form_id'] );
    if ( ! $form
      || get_post_type( $form ) !== 'form'
      || ! isset( $_POST[$form->post_name . '_nonce'] )
      || ! wp_verify_nonce( $_POST[$form->post_name . '_nonce'], $form->post_name )
    ) {
      wp_send_json( array( 'failure' => __( 'Security verification failed.', 'wm-forms' ) ) );
    }
    $result = self::validate_fields( $form );
    if ( is_wp_error( $result ) ) {
      wp_send_json( $result );
    } else {
      $settings = get_post_meta( $form->ID, 'form_settings', true );
      add_post_meta( $form->ID, 'form_results', $result );
      if ( $settings['send'] && $settings['email'] ) {
        self::send_result( $settings['email'], $form, $result );
      }
      if ( $settings['success'] === 'redirect' ) {
        wp_send_json( array( 'redirect' => $settings['redirect'] ) );
      }
      wp_send_json( array( 'success' => __( $settings['message'], 'wm-forms' ) ) );
    }
  }

  private static function validate_fields( $form )
  {
    $fields = get_form_fields( $form->ID );
    $akismet = array(
      'comment_content' => '',
      'permalink' => get_permalink( $form->ID ),
      'comment_post_modified_gmt' => $form->post_modified
    );
    $errors = new WP_Error();
    $result = array();
    foreach ( $fields as $name => $field ) {
      $input = array_key_exists( $name, $_POST ) ? trim( $_POST[$name] ) : null;
      if ( empty( $input ) ) {
        if ( $field['required'] ) {
          $errors->add( $name, __( 'This field is required.', 'wm-forms' ) );
        }
        continue;
      }
      switch ( $field['type'] )
      {
        case 'checkbox':
        $value = ! empty( $input );
        break;

        case 'radio':
        case 'select':
        $value = sanitize_key( $input );
        if ( ! array_key_exists( $value, $field['options'] ) ) {
          $errors->add( $name, __( 'This is not a valid option.', 'wm-forms' ) );
        }
        break;

        case 'email':
        $value = sanitize_email( $input );
        if ( ! is_email( $value ) ) {
          $errors->add( $name, __( 'Please enter a valid email address.', 'wm-forms' ) );
          continue 2;
        }
        if ( ! isset( $akismet['comment_author_email'] ) ) {
          $akismet['comment_author_email'] = $value;
        }
        break;

        case 'url':
        $value = esc_url( $input );
        if ( ! parse_url( $value ) ) {
          $errors->add( $name, __( 'Please enter a valid URL.', 'wm-forms' ) );
          continue 2;
        }
        if ( ! isset( $akismet['comment_author_url'] ) ) {
          $akismet['comment_author_url'] = $value;
        }
        break;

        default:
        $value = sanitize_text_field( $input );
        $akismet['comment_content'] .= "{$value}\r\n";
        break;
      }
      $result[$name] = $value;
    }
    if ( $errors->get_error_code() ) {
      return $errors;
    }
    if ( self::is_spam( $akismet ) ) {
      wp_send_json( array( 'failure' => __( 'Submission interpreted as spam !', 'wm-forms' ) ) );
    }
    return $result;
  }

  private static function is_spam( $data ) {
		if ( function_exists( 'akismet_http_post' ) && ( get_option( 'wordpress_api_key' ) || defined('WPCOM_API_KEY') ) ) {
			global $akismet_api_host, $akismet_api_port;
      $data = array_merge( $data, $_SERVER, array(
        'blog'         => home_url(),
        'user_ip'      => $_SERVER['REMOTE_ADDR'],
        'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
        'referrer'     => $_SERVER['HTTP_REFERER'],
        'comment_type' => 'custom-form',
        'blog_lang'    => get_bloginfo( 'language' ),
        'blog_charset' => get_bloginfo( 'charset' )
      ) );
      $response = akismet_http_post( http_build_query( $data ), $akismet_api_host, '/1.1/comment-check', $akismet_api_port );
      return ( $response[1] === 'true' );
    }
    return false;
  }

  private static function send_result( $email, $form, $result )
  {
    $author_email = get_the_author_meta( 'user_email', $form->post_author );
		$subject = get_bloginfo( 'name' ) . ' &raquo; ' . $form->post_title;
    $fields = get_form_fields( $form->ID );
    $result = WM_Form_Results::parse( $fields, $result, false );
		$body = sprintf( __( 'A new submission was recorded on the form "%s" (%s).' ), $form->post_title, get_permalink( $form->ID ) ) . "\r\n\r\n";
    foreach ( $fields as $name => $field ) {
      $body .= "\r\n[{$field['label']}]\r\n{$result[$name]}\r\n";
    }
		$body .= "\r\n\r\n" . sprintf( __( 'You receive this message since it is defined like so in the form settings. If you do not want to get these notifications anymore, unsuscribe from %s, or contact the form author (%s).' ), get_edit_post_link( $form->ID ), $author_email );
		return wp_mail( $email, $subject, $body );
  }
}
add_action( 'init', array( WM_Forms_Validate, 'init' ) );
