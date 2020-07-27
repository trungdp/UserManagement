<?php
if ( ! class_exists( 'WP_Post_ACL' ) ) : // thoát nếu truy cập trực tiếp

//Class Post_ACL là class thực hiện chức năng cho phép người dùng chỉ định 
//những người dùng có quyền edit post có được phép chỉnh chỉnh sửa 
//post đó hay không. Các hàm chính của class này là:
// - metabox_acl(): cho phép thêm một meta box vào trang edit post, meta box 
//  này chứa một check list các ngừoi dùng có quyền edit. 
// - has_edit_permission(): kiểm tra user có quyền edit hay không 

class Post_ACL {
  public static $instance;
  public $post_types;

  public static function init() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new Post_ACL();
    }
    return self::$instance;
  }

  private function __construct() {
    $this->post_types = defined('ACL_POST_TYPES') ? unserialize( ACL_POST_TYPES ) : [ 'post', 'page' ];

    add_filter( 'user_has_cap', array( $this, 'check_post_edit_acl' ), 10, 3 );

    add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
    add_action( 'save_post', array( $this, 'save_permissions' ) );

    add_action( 'plugins_loaded', array( $this, 'load_our_textdomain' ) );
  }

  public function check_post_edit_acl( $allcaps, $cap, $args ) {
    if ( 'edit_post' != $args[0])
      return $allcaps;

    if ( !isset( $args[2] ) ) {
      return $allcaps;
    }

    // post data
    $post = get_post( $args[2] );

    if( !in_array( $post->post_type, $this->post_types ) ) {
      return $allcaps;
    }

    $user_id = $args[1];

    $editors = self::get_editors();
    $editor_ids = array_map( function( $user ) { return $user->ID; }, $editors );
    if( !in_array( $user_id, $editor_ids ) ) {
      return $allcaps;
    }

    if( $this->has_edit_permissions( $post->ID, $user_id ) ) {
      return $allcaps;
    }

    $allcaps[$cap[0]] = false;

    return $allcaps;
  }

  public function add_meta_box() {
    if( current_user_can( 'remove_users' ) ) {
      // Shortcode meta box
      add_meta_box(
        'post-acl',
        __( 'Quyền chỉnh sửa', 'wp-post-acl' ),
        array( $this, 'metabox_acl' ),
        $this->post_types,
        'side',
        'default'
      );
    }
  }

  public function metabox_acl( $post ) {
    $editors = self::get_editors();
    if( empty( $editors ) ) {
?>
<p><?php _e('Không tìm thấy <em>editor</em> nào.', 'wp-post-acl'); ?></p>
<?php
      return;
    }
    $permissions = get_post_meta( $post->ID, '_acl_edit_permissions', true );
?>
<ul class="acl-list">
<?php foreach( $editors as $editor ) : ?>
  <li>
    <?php $checked = $this->has_edit_permissions( $post->ID, $editor ); ?>
    <label><input value="<?php echo $editor->user_nicename; ?>" type="checkbox" name="acl_users[]" <?php echo $checked ? 'checked' : ''; ?>> <?php echo $editor->display_name; ?></label>
  </li>
<?php endforeach; ?>
</ul>
<?php
    wp_nonce_field( 'wp_post_acl_meta', 'wp_post_acl_meta_nonce' );
  }

  public function has_edit_permissions( $post_id, $user ) {
    $permissions = get_post_meta( $post_id, '_acl_edit_permissions', true );

    if( ! $user instanceof WP_User ) {
      if( is_numeric( $user ) ) {
        $user = get_user_by( 'id', $user );
      }
      else {
        $user = get_user_by( 'slug', $user );
      }
    }

    return isset( $permissions[ $user->user_nicename ] ) && $permissions[ $user->user_nicename ] === false ? false : true;
  }

  /**
   * Save ACL options for post
   */
  public function save_permissions( $post_id ) {
    // verify nonce
    if ( ! isset( $_POST['wp_post_acl_meta_nonce'] ) ) {
      return;
    }
    else if ( ! wp_verify_nonce( $_POST['wp_post_acl_meta_nonce'], 'wp_post_acl_meta' ) ) {
      return;
    }

    // check permissions
    if( ! current_user_can( 'remove_users' ) ) {
      return;
    }

    // check valid post type
    if ( !isset( $_POST['post_type'] ) || ! in_array( $_POST['post_type'], $this->post_types ) ) {
      return;
    }

    $permissions = array();
    $editors = self::get_editors();
    foreach( $editors as $editor ) {
      if( isset( $_POST['acl_users'] ) && is_array( $_POST['acl_users'] )) {
        $permissions[ $editor->user_nicename ] = in_array( $editor->user_nicename, $_POST['acl_users'] );
      }
      else {
        $permissions[ $editor->user_nicename ] = false;
      }
    }
    update_post_meta( $post_id, '_acl_edit_permissions', $permissions );
  }

  /**
   * Get list of users acl applies to
   */
  private static function get_editors() {
    return apply_filters( 'acl_get_editors', get_users([ 'role__in' => ['editor','author'] ]) );
  }

  /**
   * Load our plugin textdomain
   */
  public static function load_our_textdomain() {
    load_plugin_textdomain( 'wp-post-acl', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
  }
}

endif;

Post_ACL::init();
