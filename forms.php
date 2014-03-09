<?php

class WM_Forms
{
  public static function init()
  {
    add_filter( 'the_content', array( __CLASS__, 'the_content' ) );
  }

  public static function the_content( $content )
  {
    if ( 'form' === get_post_type() ) {
      return $content . self::get_form( get_the_ID() );
    }
    return $content;
  }

  private static function get_form( $post_id )
  {
    $post = get_post( $post_id );
    $fields = wm_get_form_fields( $post_id );
    $settings = get_post_meta( get_the_ID(), 'form_settings', true );
    foreach ( $fields as $name => $field ) {
      $required = $field['required'] ? 'required' : '';
      $content .= "<p>";
      switch ( $field['type'] )
      {
        case 'checkbox':
        $content .= "<label><input name='{$name}' {$required} type='checkbox' value='1' /> {$field['label']}</label>";
        break;

        case 'textarea':
        $content .= "<label for='wm-form-{$name}'>{$field['label']}</label><br>";
        $content .= "<textarea name='{$name}' {$required} id='wm-form-{$name}'></textarea>";
        break;

        case 'radio':
        $content .= "<label>{$field['label']}</label>";
        foreach ( $field['options'] as $k => $label ) {
          $content .= "<br><label><input type='radio' name='{$name}' {$required} value='$k'> {$label}</label>";
        }
        break;

        case 'select':
        $content .= "<label for='wm-form-{$name}'>{$field['label']}</label><br>";
        $content .= "<select name='{$name}' {$required} id='wm-form-{$name}'>";
        foreach ( $field['options'] as $k => $label ) {
          $content .= "<option value='$k'>{$label}</option>";
        }
        $content .= "</select>";
        break;

        default:
        $content .= "<label for='wm-form-{$name}'>{$field['label']}</label><br>";
        $content .= "<input name='{$name}' {$required} id='wm-form-{$name}' type='{$field['type']}' />";
        break;
      }
      $content .= "</p>";
    }
    $submit = "<input type='submit' value='{$settings['submit']}'>";
    $form = "<form class='wm-form'>";
    $form .= wp_nonce_field( $post->post_name, $post->post_name . '_nonce', true, false );
    $form .= "<input type='hidden' name='wm_form_id' value='{$post_id}'>";
    $form .= apply_filters( 'wm_form_fields', $content, $fields );
    $form .= apply_filters( 'wm_form_submit', $submit, $settings['submit'] );
    $form .= "</form>";
    return $form;
  }
}
add_action( 'init', array( WM_Forms, 'init' ) );
