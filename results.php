<?php

class WM_Form_Results
{
  public static function init()
  {
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
    add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
  }

  public static function parse( $fields, $result, $html = true )
  {
    foreach ( $fields as $name => $field ) {
      switch ($field['type'])
      {
        case 'checkbox':
      		$result[$name] = $result[$name] ? __( 'No', 'wm-forms' ) : __( 'Yes', 'wm-forms' );
      		break;
        case 'radio':
        case 'select':
          $result[$name] = $field['options'][$result[$name]];
        	break;
      }
      if ( $html ) {
        switch ( $field['type'] )
        {
          case 'email':
            $result[$name] = "<a href='mailto:{$result[$name]}'>{$result[$name]}</a>";
          	break;
          case 'url':
            $result[$name] = "<a href='{$result[$name]}'>{$result[$name]}</a>";
          	break;
        }
      }
    }
    return $result;
  }

  public static function admin_enqueue_scripts( $hook_suffix )
  {
    if ( $hook_suffix === 'form_page_results' ) {
      wp_enqueue_style( 'wm-forms-results', plugins_url( 'css/results.css' , __FILE__ ) );
      wp_register_script( 'jquery-tablesorter', plugins_url( 'js/vendor/jquery.tablesorter.min.js' , __FILE__ ), array( 'jquery' ), null, true );
      wp_enqueue_script( 'wm-forms-results', plugins_url( 'js/results.js' , __FILE__ ), array( 'jquery', 'jquery-tablesorter' ), null, true );
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
      $fields = get_form_fields( $form_id );
      $results = get_form_results( $form_id ); ?>
      <h2><?php _e( 'Form Results', 'wm-forms' ); ?></h2>
      <!-- <div class="tablenav top"></div> -->
      <table class="widefat fixed" cellspacing="0" id="form-results">
        <thead>
          <tr>
            <th class="check-column">
              <input class="select-all" type="checkbox">
            </th>
            <th>
              <span><?php _e( 'Date', 'wm-forms' ); ?></span>
              <span class='sorting-indicator'></span>
            </th>
            <?php foreach ( $fields as $field ) {
              echo "<th>
                <span>{$field['label']}</span>
                <span class='sorting-indicator'></span>
              </th>";
            } ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $results as $i => $result ) { ?>
            <tr<?php if ( $i % 2 == 0 ) { echo ' class="alt"'; } ?>>
              <td>
                <input class="select-result" type="checkbox" name="results[]" value="<?php echo $result['result_id']; ?>">
              </td>
              <td>
                <span class="submitted-on"><?php
                  $timestamp = hexdec( substr( $result['result_id'], 0, 8 ) );
                  echo date( 'Y-m-d', $timestamp ) . '<br>' . date( 'H:i:s', $timestamp );
                ?></span><br>
                <span class="trash">
                  <a href="" class="delete" title="<?php _e( 'Delete this result' ); ?>"><?php _e( 'Delete' ); ?></a>
                </span>
              </td>
              <?php foreach ( $fields as $name => $field ) {
                echo "<td>{$result[$name]}</td>";
              } ?>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  <?php }

  private static function form_results_selector()
  {
    $forms = get_forms( array( 'numberposts' => -1 ) );
    echo "<form method='get' id='forms-select'>
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
add_action( 'init', array( 'WM_Form_Results', 'init' ) );
