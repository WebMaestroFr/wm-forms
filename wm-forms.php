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
Text Domain: wm_forms
*/

class WM_Forms
{
	public static function init()
	{
		self::register_post_type();
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
    add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
		add_action( 'save_post', array( __CLASS__, 'save_form' ) );
		add_action( 'wp_ajax_wm_form', array( __CLASS__, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_wm_form', array( __CLASS__, 'ajax' ) );
    add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
    add_filter( 'the_content', array( __CLASS__, 'the_content' ) );
		add_filter( 'wp_dropdown_pages', array( __CLASS__, 'dropdown_pages' ) );
	}

	public static function register_post_type()
	{
		$labels = array(
			'name'                => __( 'Forms', 'wm_forms' ),
			'singular_name'       => __( 'Form', 'wm_forms' ),
			'menu_name'           => __( 'Forms', 'wm_forms' ),
			'parent_item_colon'   => __( 'Parent Form:', 'wm_forms' ),
			'all_items'           => __( 'All Forms', 'wm_forms' ),
			'view_item'           => __( 'View Form', 'wm_forms' ),
			'add_new_item'        => __( 'Add New Form', 'wm_forms' ),
			'add_new'             => __( 'Add New', 'wm_forms' ),
			'edit_item'           => __( 'Edit Form', 'wm_forms' ),
			'update_item'         => __( 'Update Form', 'wm_forms' ),
			'search_items'        => __( 'Search Form', 'wm_forms' ),
			'not_found'           => __( 'Not found', 'wm_forms' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'wm_forms' ),
		);
		$args = array(
			'label'              	=> __( 'wm_form', 'wm_forms' ),
			'description'        	=> __( 'Description', 'wm_forms' ),
			'labels'             	=> $labels,
			'supports'        	   => array( 'title', 'thumbnail', 'excerpt' ), // 'page-attributes'
			'hierarchical'       	=> false,
			'public'             	=> true,
			'show_ui'            	=> true,
			'show_in_menu'       	=> true,
			'show_in_nav_menus'  	=> true,
			'show_in_admin_bar'  	=> true,
			'menu_position'      	=> 25,
			'menu_icon'          	=> 'dashicons-list-view',
			'can_export'         	=> true,
			'has_archive'        	=> true,
			'exclude_from_search'	=> false,
			'publicly_queryable' 	=> true,
			'capability_type'    	=> 'page',
			'register_meta_box_cb' => array( __CLASS__, 'register_meta_box' ),
			'rewrite'							=> array( 'slug' => 'form' )
		);
		register_post_type( 'wm_form', $args );
	}

	public static function register_meta_box()
	{
		add_meta_box( 'wm-form-fields', __( 'Fields', 'wm_forms' ), array( __CLASS__, 'meta_box_fields' ), 'wm_form', 'normal' );
		add_meta_box( 'wm-form-action', __( 'Action', 'wm_forms' ), array( __CLASS__, 'meta_box_action' ), 'wm_form', 'side', 'core' );
	}

	public static function meta_box_fields( $post, $metabox )
	{ $fids = absint( get_post_meta( $post->ID, 'form_fields_increment', true ) ); ?>
		<input type="hidden" name="wm_form_fields" class="wm-form-fields-json" value='<?php echo get_post_meta( $post->ID, 'form_fields', true ); ?>' />
		<input type="hidden" name="wm_form_fields_increment" class="wm-form-fields-increment" value="<?php echo $fids; ?>" />
		<div class="wm-form-fields-list"></div>
		<button class="button button-large right wm-form-add-field"><?php _e( 'Add Field', 'wm_forms' ); ?></button>
		<script type="text/template" class="wm-form-field-template">
			<?php include( plugin_dir_path( __FILE__ ) ) . 'tpl/field.php'; ?>
		</script>
	<?php }

	public static function meta_box_action( $post, $metabox )
	{
		$value = get_post_meta( $post->ID, 'form_action', true );
		wp_nonce_field( 'wm_form', 'wm_form_nonce' );
		global $current_user;
		get_currentuserinfo(); ?>
		<div class="misc-pub-section">
			<label>
				<input type="checkbox" <?php checked( isset( $value['send'] ) ); ?> value="1" name="wm_form_action[send]">
				<?php _e( 'Send results by email to', 'wm_forms' ); ?>
			</label>
			<input type="email" name="wm_form_action[email]" value="<?php echo ( isset( $value['email'] ) && is_email( $value['email'] ) ) ? $value['email'] : $current_user->user_email; ?>" style="padding: 1px 3px; font-size: 12px;">
		</div>
		<div class="misc-pub-section">
			<label>Submit Text</label>
			<input type="text"  name="wm_form_action[submit]"class="widefat" value="<?php echo ( isset( $value['submit'] ) ) ? $value['submit'] : $post->post_title; ?>">
		</div>
	<?php }

  public static function admin_enqueue_scripts( $hook_suffix )
  {
  	wp_enqueue_style( 'wm-forms', plugins_url( 'wm-forms.css' , __FILE__ ) );
    wp_enqueue_script( 'wm-forms', plugins_url( 'js/wm-forms.js' , __FILE__ ), array( 'jquery', 'underscore' ) );
  }

	public static function admin_menu()
	{
		add_submenu_page( 'edit.php?post_type=wm_form', __( 'Form Results', 'wm_forms' ), __( 'Results', 'wm_forms' ), 'manage_options', 'wm_form_results', array( __CLASS__, 'do_results_page' ) );
	}

	public static function do_results_page()
	{
		$select = "<form style='float: left; margin: 9px 8px 4px 0;' method='get' class='wm-form-results'>
			<input type='hidden' name='post_type' value='wm_form'>
			<input type='hidden' name='page' value='wm_form_results'>
			<select name='wm_form_id'>";
		$forms = get_posts( array(
			'post_type'		=> 'wm_form',
			'numberposts'	=> -1
		) );
		foreach( $forms as $form ) {
			if ( ! isset( $form_id ) ) {
				$form_id = isset( $_GET['wm_form_id'] ) ? $_GET['wm_form_id'] : $form->ID;
			}
			$selected = selected( $form_id, $form->ID, false );
			$select .= "<option value='{$form->ID}' {$selected}>{$form->post_title}</option>";
		}
		$select .= "</select></form>";
		$fields = json_decode( get_post_meta( $form_id, 'form_fields', true ), true );
?>
    <div class="wrap">
      <?php echo $select; ?><h2><?php _e( 'Form Results', 'wm_forms' ); ?></h2>
      <!-- <div class="tablenav top"></div> -->
      <table class="widefat fixed" cellspacing="0">
        <thead>
          <tr>
            <th scope="col" id="cb" class="manage-column column-cb check-column">
            	<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
            	<input id="cb-select-all-1" type="checkbox">
            </th>
            <?php foreach ( $fields as $field ) {
            	echo "<th scope='col' class='manage-column'>{$field['label']}</th>";
            } ?>
          </tr>
        </thead>
      </table>
    </div>
<?php
	}

	public static function pre_get_posts( $query )
	{
		// Without this, a form as front page would rewrite the base URL...
		// http://wpquestions.com/question/show/id/4112
		if ( ! $query->query_vars['post_type'] && $query->query_vars['page_id'] ) {
			$query->query_vars['post_type'] = array( 'page', 'wm_form' );
		}
	}

	public static function save_form( $post_id ) {
		if ( 'wm_form' !== $_POST['post_type']
				|| ! isset( $_POST['wm_form_nonce'] )
				|| ! wp_verify_nonce( $_POST['wm_form_nonce'], 'wm_form' )
				|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				|| ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		update_post_meta( $post_id, 'form_fields', $_POST['wm_form_fields'] );
		update_post_meta( $post_id, 'form_fields_increment', $_POST['wm_form_fields_increment'] );
		update_post_meta( $post_id, 'form_action', $_POST['wm_form_action'] );
	}

	public static function ajax()
	{
		$post = get_post( $_POST['wm_form'] );
		if ( ! wp_verify_nonce( $_POST[$post->post_name . '_nonce'], $post->post_name ) ) {
			wp_die( __( 'Security verification failed.', 'wm_forms' ) );
		}
		$fields = wm_get_form_fields( $post->ID );
		$results = array();
		foreach ( $fields as $name => $field ) {
			switch ( $field['type'] )
      {
    		case 'checkbox':
					$results[] = ! empty( $_POST[$name] );
    		  break;

  			case 'radio':
  			case 'select':
					$results[] = sanitize_key( $_POST[$name] );
    		  break;

  			case 'email':
					$results[] = sanitize_email( $_POST[$name] );
    		  break;

  			default:
					$results[] = sanitize_text_field( $_POST[$name] );
  			  break;
  		}
		}
		wp_send_json( $results );
	}

  public static function enqueue_scripts()
  {
  	wp_enqueue_script( 'jquery-validate', plugins_url( 'js/jquery.validate.min.js' , __FILE__ ), array( 'jquery' ), null, true );
  	wp_enqueue_script( 'wm-forms-validate', plugins_url( 'js/wm-forms-validate.js' , __FILE__ ), array( 'jquery-validate', 'jquery-serialize-object' ), null, true );
		wp_localize_script( 'wm-forms-validate', 'ajaxUrl', admin_url( 'admin-ajax.php' ) );
  }

  public static function the_content( $content )
  {
    if ( 'wm_form' === get_post_type() ) {
			return $content . self::get_the_form( get_the_ID() );
		}
    return $content;
	}

  public static function get_the_form( $post_id )
  {
			$post = get_post( $post_id );
    	$fields = wm_get_form_fields( $post_id );
			$action = get_post_meta( get_the_ID(), 'form_action', true );
      foreach ( $fields as $name => $field ) {
				$required = $field['required'] ? 'required' : '';
        $content = "<p>";
        switch ( $field['type'] )
        {
    			case 'checkbox':
    			  $content .= "<label><input name='{$name}' {$required} type='checkbox' value='1' /> {$field['label']}</label>";
    			  break;

    			case 'textarea':
    			  $content .= "<label for='wm-form-{$name}'>{$field['label']}</label>";
    			  $content .= "<textarea name='{$name}' {$required} id='wm-form-{$name}'></textarea>";
    			  break;

    			case 'radio':
    			  $content .= "<fieldset><legend>{$field['label']}</legend>";
    			  foreach ( $field['options'] as $k => $label ) {
    			    $content .= "<label><input type='radio' name='{$name}' {$required} value='$k'> {$label}</label>";
    			  }
    			  $content .= "</fieldset>";
    			  break;

    			case 'select':
    			  $content .= "<label for='wm-form-{$name}'>{$field['label']}</label>";
    			  $content .= "<select name='{$name}' {$required} id='wm-form-{$name}'>";
    			  foreach ( $field['options'] as $k => $label ) {
    			    $content .= "<option value='$k'>{$label}</option>";
    			  }
    			  $content .= "</select>";
    			  break;

    			default:
    			  $content .= "<label for='wm-form-{$name}'>{$field['label']}</label>";
    			  $content .= "<input name='{$name}' {$required} id='wm-form-{$name}' type='{$field['type']}' />";
    			  break;
    		}
    		$content .= "</p>";
      }
			$submit = "<input type='submit' value='{$action['submit']}'>";
      $form = "<form>";
			$form .= wp_nonce_field( $post->post_name, $post->post_name . '_nonce', true, false );
			$form .= "<input type='hidden' name='wm_form' value='{$post_id}'>";
			$form .= apply_filters( 'wm_form_fields', $content, $fields );
			$form .= apply_filters( 'wm_form_submit', $submit, $action['submit'] );
			$form .= "</form>";
			return $form;
  }

  public static function dropdown_pages( $select )
	{
		// Allow to select a form as front page
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
    return str_replace( '</select>', '<optgroup label="' . __( 'Forms', 'wm_forms' ) . '">' . $options . '</optgroup></select>', $select );
	}
}
add_action( 'init', array( WM_Forms, 'init' ) );

function wm_get_forms( $args = array() ) {
	return get_posts( array_merge( $args, array( 'post_type' => 'wm_form' ) ) );
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
