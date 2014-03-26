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
GitHub Plugin URI: https://github.com/WebMaestroFr/wm-forms
GitHub Branch: master
*/

include( plugin_dir_path( __FILE__ ) . 'forms.php' );
include( plugin_dir_path( __FILE__ ) . 'functions.php' );
include( plugin_dir_path( __FILE__ ) . 'results.php' );
include( plugin_dir_path( __FILE__ ) . 'validate.php' );

class WM_Forms_Plugin
{
  public static function init()
  {
    self::register_post_type();
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
    add_action( 'save_post', array( __CLASS__, 'save_form' ) );
    add_filter( 'wp_dropdown_pages', array( __CLASS__, 'dropdown_pages' ) );
    add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
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
      'description'          => __( 'Form to gather informations from users.', 'wm-forms' ),
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
    if ( get_post_type() === 'form'
      && ( $hook_suffix === 'post-new.php' || $hook_suffix === 'post.php' )
    ) {
      wp_enqueue_style( 'wm-forms', plugins_url( 'css/wm-forms.css' , __FILE__ ) );
      wp_enqueue_script( 'wm-forms', plugins_url( 'js/wm-forms.js' , __FILE__ ), array(
        'jquery',
        'jquery-ui-sortable',
        'underscore'
      ) );
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
    global $current_user;
    get_currentuserinfo();
    wp_nonce_field( 'wm_form', 'wm_form_nonce' );
    $settings = get_post_meta( $post->ID, 'form_settings', true );
    if ( ! isset( $settings['success'] ) ) { $settings['success'] = 'message'; }?>
    <div>
      <label for="wm-form-settings-submit">Submit text</label>
      <input type="text" name="wm_form_settings[submit]" class="widefat" value="<?php
        echo ( isset( $settings['submit'] ) ) ? $settings['submit'] : __( 'Submit', 'wm-forms' );
      ?>" id="wm-form-settings-submit">
    </div>
    <fieldset>
      <legend>On successful submission</legend>
      <div>
        <input type="radio" <?php checked( $settings['success'], 'message' ); ?> name="wm_form_settings[success]" value="message" id="wm-form-settings-success-message">
        <label for="wm-form-settings-success-message"><?php _e( 'Display message', 'wm-forms' ); ?></label>
        <textarea name="wm_form_settings[message]" class="widefat"><?php
          echo ( isset( $settings['message'] ) ) ? $settings['message'] : __( 'Submission successful.', 'wm-forms' );
        ?></textarea>
        <input type="radio" <?php checked( $settings['success'], 'redirect' ); ?> name="wm_form_settings[success]" value="redirect" id="wm-form-settings-success-redirect">
        <label for="wm-form-settings-success-redirect"><?php _e( 'Redirect to', 'wm-forms' ); ?></label>
        <input type="url" name="wm_form_settings[redirect]" value="<?php
          echo ( isset( $settings['redirect'] ) && parse_url( $settings['redirect'] ) ) ? $settings['redirect'] : '';
        ?>" id="wm-form-settings-redirect">
      </div>
      <div>
        <input type="checkbox" <?php checked( isset( $settings['send'] ) ); ?> value="1" name="wm_form_settings[send]">
        <label><?php _e( 'Send results by email to', 'wm-forms' ); ?></label>
        <input type="email" name="wm_form_settings[email]" value="<?php
          echo ( isset( $settings['email'] ) && is_email( $settings['email'] ) ) ? $settings['email'] : $current_user->user_email;
        ?>" id="wm-form-settings-email">
      </div>
    </fieldset>
  <?php }

  public static function save_form( $post_id ) {
    if ( 'form' !== $_POST['post_type']
      || ! isset( $_POST['wm_form_nonce'] )
      || ! wp_verify_nonce( $_POST['wm_form_nonce'], 'wm_form' )
      || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      || ! current_user_can( 'edit_post', $post_id )
    ) {
      return $post_id;
    }
    update_post_meta( $post_id, 'form_fields', $_POST['wm_form_fields'] );
    update_post_meta( $post_id, 'form_fields_increment', $_POST['wm_form_fields_increment'] );
    update_post_meta( $post_id, 'form_settings', $_POST['wm_form_settings'] );
    return $post_id;
  }

  public static function dropdown_pages( $select )
  {
    // Allow to select a form as front page
    if ( false === strpos( $select, " name='page_on_front'" ) ) { return $select; }
    $forms = get_forms( array(
      'numberposts'    => -1,
      'order'          => 'ASC',
      'orderby'        => 'title'
    ) );
    if ( ! $forms ) { return $select; }
    $current = get_option( 'page_on_front', 0 );
    $options = walk_page_dropdown_tree( $forms, 0, array(
      'selected'              => $current,
      'echo'                  => 0,
      'name'                  => 'page_on_front'
    ) );
    $default = '<option value="0">&mdash; Select &mdash;</option>';
    $select = str_replace( $default, $default . '<optgroup label="' . __( 'Pages', 'wm-forms' ) . '">', $select );
    return str_replace( '</select>', '</optgroup><optgroup label="' . __( 'Forms', 'wm-forms' ) . '">' . $options . '</optgroup></select>', $select );
  }

  public static function pre_get_posts( $query )
  {
    // Without this, a form as front page would rewrite its base URL...
    // http://wpquestions.com/question/show/id/4112
    if ( ! $query->query_vars['post_type']
      && $query->query_vars['page_id']
    ) {
      $query->query_vars['post_type'] = array( 'page', 'form' );
    }
  }
}
add_action( 'init', array( WM_Forms_Plugin, 'init' ) );
