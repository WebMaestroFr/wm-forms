<?php
/*
Plugin Name: WebMaestro Forms
Plugin URI: http://#
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Forms Post Type Manager
Version: 1.0
License: GNU General Public License
License URI: license.txt
Text Domain: wm-forms
*/

include( plugin_dir_path( __FILE__ ) . 'forms.php' );
include( plugin_dir_path( __FILE__ ) . 'results.php' );
include( plugin_dir_path( __FILE__ ) . 'validate.php' );

function wm_get_forms( $args = array() ) {
  return get_posts( array_merge( $args, array( 'post_type' => 'form' ) ) );
}

function wm_get_form_fields( $post_id ) {
  $post = get_post( $post_id );
  $meta = json_decode( get_post_meta( $post_id, 'form_fields', true ), true );
  $fields = array();
  foreach ( $meta as $field ) {
    $fields["{$post->post_name}-{$field['fid']}"] = $field;
  }
  return $fields;
}

function wm_get_form_results( $post_id ) {
  global $wpdb;
  $results = $wpdb->get_results(
    $wpdb->prepare( "SELECT `id`, `value`, `date` FROM `{$wpdb->prefix}form_results` WHERE `form_id` = %d;", $post_id )
  );
  $fields = json_decode( get_post_meta( $form_id, 'form_fields', true ), true );
  foreach ( $results as $result ) {
    $result->value = json_decode( $result->value, true );
    foreach ( $fields as $field ) {
      $value = $result->value[$field['fid']];
      switch ($field['type'])
      {
        case 'checkbox':
      		$value = $value ? __( 'Yes', 'wm-forms' ) : __( 'No', 'wm-forms' );
      		break;

        case 'radio':
        case 'select':
          $value = $field['options'][$value];
        	break;

        case 'email':
          $value = "<a href='mailto:{$value}'>{$value}</a>";
        	break;

        case 'url':
          $value = "<a href='{$value}'>{$value}</a>";
        	break;
      }
      $result->value[$field['fid']] = $value;
    }
  }
  return $results;
}

class WM_Forms_Plugin
{
  public static function init()
  {
    register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
    register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivation' ) );
    self::register_post_type();
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
    add_action( 'save_post', array( __CLASS__, 'save_form' ) );
    add_filter( 'wp_dropdown_pages', array( __CLASS__, 'dropdown_pages' ) );
    add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
  }

  public static function activation()
  {
    global $wpdb;
    $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}form_results` (
      `ID`  bigint(20) NOT NULL AUTO_INCREMENT,
      `form_id` bigint(20) NOT NULL,
      `value` longtext,
      `date` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY `ID` (`ID`),
      KEY `form_id` (`form_id`)
    );" );
  }

  public static function deactivation()
  {
    global $wpdb;
    // TODO : Ask for backup
    $wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}form_results`;" );
  }

  public static function register_post_type()
  {
    $labels = array(
      'name'                => __( 'Forms', 'wm-forms' ),
      'singular_name'       => __( 'Form', 'wm-forms' ),
      'menu_name'           => __( 'Forms', 'wm-forms' ),
      'parent_item_colon'   => __( 'Parent Form', 'wm-forms' ),
      'all_items'           => __( 'All Forms', 'wm-forms' ),
      'view_item'           => __( 'View Form', 'wm-forms' ),
      'add_new_item'        => __( 'Add New Form', 'wm-forms' ),
      'add_new'             => __( 'Add New', 'wm-forms' ),
      'edit_item'           => __( 'Edit Form', 'wm-forms' ),
      'update_item'         => __( 'Update Form', 'wm-forms' ),
      'search_items'        => __( 'Search Form', 'wm-forms' ),
      'not_found'           => __( 'Not forms found', 'wm-forms' ),
      'not_found_in_trash'  => __( 'Not forms found in Trash', 'wm-forms' ),
    );
    $args = array(
      'label'                => __( 'form', 'wm-forms' ),
      'description'          => __( 'Form to gather user informations.', 'wm-forms' ),
      'labels'               => $labels,
      'supports'             => array( 'title', 'thumbnail', 'excerpt' ),
      'public'               => true,
      'menu_position'        => '25.1',
      'menu_icon'            => 'dashicons-list-view', // TODO : create a real icon
      'has_archive'          => true,
      'capability_type'      => 'page',
      'register_meta_box_cb' => array( __CLASS__, 'register_meta_box' ),
      'rewrite'              => array( 'slug' => __( 'form', 'wm-forms' ) )
    );
    register_post_type( 'form', $args );
  }

  public static function admin_enqueue_scripts( $hook_suffix )
  {
    if ( get_post_type() === 'form' && ( $hook_suffix === 'post-new.php' || $hook_suffix === 'post.php' ) ) {
      if ( ! class_exists( WM_Settings ) ) {
        add_action( 'admin_notices', function () {
          echo '<div class="error"><p>' . __( 'The plugin <strong>Less Compiler</strong> requires the plugin <strong><a href="https://github.com/WebMaestroFr/wm-settings">WebMaestro Settings</a></strong> in order to display the options pages.', 'wm-forms' ) . '</p></div>';
        });
      }
      wp_enqueue_style( 'wm-forms', plugins_url( 'css/wm-forms.css' , __FILE__ ) );
      wp_enqueue_script( 'wm-forms', plugins_url( 'js/wm-forms.js' , __FILE__ ), array( 'jquery', 'jquery-ui-sortable', 'underscore' ) );
    }
  }

  public static function register_meta_box()
  {
    add_meta_box( 'wm-form-fields', __( 'Fields', 'wm-forms' ), array( __CLASS__, 'meta_box_fields' ), 'form', 'normal' );
    add_meta_box( 'wm-form-settings', __( 'Settings', 'wm-forms' ), array( __CLASS__, 'meta_box_settings' ), 'form', 'side', 'core' );
  }

  public static function meta_box_fields( $post, $metabox )
  { ?>
    <input type="hidden" name="wm_form_fields" class="wm-form-fields-json" value='<?php
      echo get_post_meta( $post->ID, 'form_fields', true );
    ?>'>
    <input type="hidden" name="wm_form_fields_increment" class="wm-form-fields-increment" value="<?php
      echo absint( get_post_meta( $post->ID, 'form_fields_increment', true ) );
    ?>">
    <div class="wm-form-fields-list"></div>
    <button class="button button-large right wm-form-add-field"><?php _e( 'Add Field', 'wm-forms' ); ?></button>
    <script type="text/template" class="wm-form-field-template"><?php
      include( plugin_dir_path( __FILE__ ) . 'tpl/field.tpl' );
    ?></script>
  <?php }

  public static function meta_box_settings( $post, $metabox )
  {
    wp_nonce_field( 'wm_form', 'wm_form_nonce' );
    global $current_user;
    get_currentuserinfo();
    $value = get_post_meta( $post->ID, 'form_settings', true ); ?>
    <div class="misc-pub-section">
      <label>
        <input type="checkbox" <?php checked( isset( $value['send'] ) ); ?> value="1" name="wm_form_settings[send]">
        <?php _e( 'Send results by email to', 'wm-forms' ); ?>
      </label>
      <input type="email" name="wm_form_settings[email]" value="<?php
        echo ( isset( $value['email'] ) && is_email( $value['email'] ) ) ? $value['email'] : $current_user->user_email;
      ?>" id="wm-form-settings-email">
    </div>
    <div class="misc-pub-section">
      <label>Submit Text</label>
      <input type="text" name="wm_form_settings[submit]" class="widefat" value="<?php
        echo ( isset( $value['submit'] ) ) ? $value['submit'] : __( 'Submit', 'wm-forms' );
      ?>">
    </div>
  <?php }

  public static function save_form( $post_id ) {
    if ( 'form' !== $_POST['post_type']
    || ! isset( $_POST['wm_form_nonce'] )
    || ! wp_verify_nonce( $_POST['wm_form_nonce'], 'wm_form' )
    || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
    || ! current_user_can( 'edit_post', $post_id ) ) {
      return $post_id;
    }
    update_post_meta( $post_id, 'form_fields', $_POST['wm_form_fields'] );
    update_post_meta( $post_id, 'form_fields_increment', $_POST['wm_form_fields_increment'] );
    update_post_meta( $post_id, 'form_settings', $_POST['wm_form_settings'] );
  }

  public static function dropdown_pages( $select ) // Allow to select a form as front page
  {
    if ( false === strpos( $select, " name='page_on_front'" ) ) { return $select; }
    $forms = wm_get_forms( array(
      'nopaging'       => true,
      'numberposts'    => -1,
      'order'          => 'ASC',
      'orderby'        => 'title'
    ) );
    if ( ! $forms ) { return $select; }
    $current = get_option( 'page_on_front', 0 );
    $options = walk_page_dropdown_tree( $forms, 0, array(
      'depth'                 => 0,
      'child_of'              => 0,
      'selected'              => $current,
      'echo'                  => 0,
      'name'                  => 'page_on_front',
      'id'                    => '',
      'show_option_none'      => '',
      'show_option_no_change' => '',
      'option_none_value'     => ''
    ) );
    return str_replace( '</select>', '<optgroup label="' . __( 'Forms', 'wm-forms' ) . '">' . $options . '</optgroup></select>', $select );
  }

  public static function pre_get_posts( $query )
  {
    // Without this, a form as front page would rewrite its base URL...
    // http://wpquestions.com/question/show/id/4112
    if ( ! $query->query_vars['post_type'] && $query->query_vars['page_id'] ) {
      $query->query_vars['post_type'] = array( 'page', 'form' );
    }
  }
}
add_action( 'init', array( WM_Forms_Plugin, 'init' ) );
