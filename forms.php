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
    $settings = get_post_meta( get_the_ID(), 'form_settings', true );
    $fields = get_form_fields( $post_id );
    $fields['form_submit'] = array(
      'type' => 'submit',
      'required' => false,
      'label' => $settings['submit']
    );
    foreach ( $fields as $name => $field ) {
      $attrs = "name='{$name}'" . ( $field['required'] ? ' required' : '' );
      $label = "<label for='form-{$name}'>{$field['label']}</label><br>";
      $content .= "<p>";
      switch ( $field['type'] )
      {
        case 'checkbox':
        $content .= "<label><input {$attrs} type='checkbox' value='1' /> {$field['label']}</label>";
        break;

        case 'textarea':
        $content .= $label . "<textarea {$attrs} id='form-{$name}'></textarea>";
        break;

        case 'radio':
        $content .= "<label>{$field['label']}</label>";
        foreach ( $field['options'] as $k => $opt ) {
          $content .= "<br><label><input type='radio' {$attrs} value='$k'> {$opt}</label>";
        }
        break;

        case 'select':
        $content .= $label . "<select {$attrs} id='form-{$name}'>";
        foreach ( $field['options'] as $k => $opt ) {
          $content .= "<option value='$k'>{$opt}</option>";
        }
        $content .= "</select>";
        break;

        case 'submit':
        $content .= "<input type='submit' value='{$field['label']}'>";
        break;

        default:
        $content .= $label . "<input {$attrs} id='form-{$name}' type='{$field['type']}' />";
        break;
      }
      $content .= "</p>";
    }
    $form = "<form class='wm-form'>";
    $form .= wp_nonce_field( $post->post_name, $post->post_name . '_nonce', true, false );
    $form .= "<input type='hidden' name='form_id' value='{$post_id}'>";
    $form .= apply_filters( 'form_fields', $content, $fields );
    $form .= "</form>";
    return $form;
  }
}
add_action( 'init', array( WM_Forms, 'init' ) );
