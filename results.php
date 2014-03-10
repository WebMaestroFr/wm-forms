<?php

class WM_Form_Results
{
  public static function init()
  {
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
    add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
  }

  public static function parse_value( $form_id, $value )
  {
    $fields = json_decode( get_post_meta( $form_id, 'form_fields', true ), true );
    $value = json_decode( $value, true );
    foreach ( $fields as $field ) {
      $v = $value[$field['fid']];
      switch ($field['type'])
      {
        case 'checkbox':
      		$v = $v ? __( 'Yes', 'wm-forms' ) : __( 'No', 'wm-forms' );
      		break;

        case 'radio':
        case 'select':
          $v = $field['options'][$v];
        	break;

        case 'email':
          $v = "<a href='mailto:{$v}'>{$v}</a>";
        	break;

        case 'url':
          $v = "<a href='{$v}'>{$v}</a>";
        	break;
      }
      $value[$field['fid']] = array( $field['label'], $v );
    }
    return $value;
  }

  public static function admin_enqueue_scripts( $hook_suffix )
  {
    if ( $hook_suffix === 'form_page_results' ) {
      wp_enqueue_style( 'wm-forms-results', plugins_url( 'css/wm-forms-results.css' , __FILE__ ) );
      wp_enqueue_script( 'wm-forms-results', plugins_url( 'js/wm-forms-results.js' , __FILE__ ), array( 'jquery' ) );
    }
  }

  public static function admin_menu()
  {
    add_submenu_page( 'edit.php?post_type=form', __( 'Form Results', 'wm-forms' ), __( 'Results', 'wm-forms' ), 'manage_options', 'results', array( __CLASS__, 'do_results_page' ) );
  }

  public static function do_results_page()
  { ?>
    <div class="wrap"><?php
      $form_id = self::form_results_selector(); // Print the <select> and return the current form ID
      $fields = json_decode( get_post_meta( $form_id, 'form_fields', true ), true );
      $results = wm_get_form_results( $form_id ); ?>
      <h2><?php _e( 'Form Results', 'wm-forms' ); ?></h2>
      <!-- <div class="tablenav top"></div> -->
      <table class="widefat fixed" cellspacing="0">
        <thead>
          <tr>
            <th class="manage-column column-cb check-column">
              <label class="screen-reader-text" for="select-all">Select All</label>
              <input id="select-all" type="checkbox">
            </th>
            <?php foreach ( $fields as $field ) {
              echo "<th scope='col' class='manage-column'>{$field['label']}</th>";
            } ?>
          </tr>
          <?php foreach ( $results as $result ) { ?>
            <tr>
              <td>
                <input type="checkbox">
              </td>
              <?php foreach ( $result->value as $v ) {
                echo "<td>{$v[1]}</td>";
              } ?>
            </tr>
          <?php } ?>
        </thead>
      </table>
    </div>
  <?php }

  private static function form_results_selector()
  {
    $forms = get_posts( array(
      'post_type'    => 'form',
      'numberposts'  => -1
    ) );
    echo "<form method='get' class='wm-form-results-selector'>
    <input type='hidden' name='post_type' value='form'>
    <input type='hidden' name='page' value='results'>
    <select name='form_id'>";
    foreach( $forms as $form ) {
      if ( ! isset( $form_id ) ) {
        $form_id = isset( $_GET['form_id'] ) ? $_GET['form_id'] : $form->ID;
      }
      $selected = selected( $form_id, $form->ID, false );
      echo "<option value='{$form->ID}' {$selected}>{$form->post_title}</option>";
    }
    echo "</select></form>";
    return $form_id;
  }
}
add_action( 'init', array( WM_Form_Results, 'init' ) );
