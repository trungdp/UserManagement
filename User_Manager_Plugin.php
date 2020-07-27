<?php
/*
	Plugin Name: User Manager
	Plugin URI:
	Description: Plugin quản lý phân quyền người dùng.
	Version: 1.0.0
	Author: Vu - Trung - Quan
	Author URI:
	License:
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Thoát nếu truy cập trực tiếp

include 'define_roles.php';         // import roles

// Các chức năng trong plugin được viết dưới dạng các Class(ngoại trừ file Restrict_Author) để
// phù hợp với tư duy lập trình hướng đối tượng.

// Class User_Manager_Plugin là class chính của dự án. có chức năng import các class chức 
// năng khác và thực hiện hoạt động cần thiết lúc kích hoạt plugin hoặc ngừng kích hoạt 
// plugin(qua hàm activation và deactivation). Một số hàm quan trọng: 
// - include(): để gọi các class chức năng khác; 
// - remove_default_role và add_new_roles để thêm các role cần thiết lúc active plugin.
class User_Manager_Plugin{

	function __construct(){
  }
  public $dir = '';
  public $uri = '';

  public static function get_instance() {
    static $instance = null;

    if ( is_null( $instance ) ) {
      $instance = new self;
      $instance->setup();
      $instance->includes();
      $instance->setup_actions();
    }

    return $instance;
  }

  private function setup() {
    // Main plugin directory path and URI.
    $this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );
    $this->uri  = trailingslashit( plugin_dir_url(  __FILE__ ) );
  }

  private function includes() {
    if ( is_admin() ) {
      require( plugin_dir_path( __FILE__ ) . '/class/Restrict-Author.php' );
      require( plugin_dir_path( __FILE__ ) . '/class/Restrict-Categories.php' );
      require( plugin_dir_path( __FILE__ ) . '/class/Post-ACL.php' );
    }
  }

  private function setup_actions() {
    register_activation_hook( __FILE__, array( $this, 'activation' ) );
    register_deactivation_hook( __FILE__, array($this, 'deactivation'));
    add_action('admin_menu', 'my_remove_sub_menus');
  }

  function my_remove_sub_menus() {
    remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category');
    remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');
  }

  public function activation() {
    // Lấy ra role administrator
    $role = get_role( 'administrator' );
    // Nếu lấy ra được role admin, thêm các cap(quyền) cần cho plugin này
    if ( ! empty( $role ) ) {
      $role->add_cap( 'restrict_content' );
      $role->add_cap( 'list_roles'       );
      if ( ! is_multisite() ) {
        $role->add_cap( 'create_roles' );
        $role->add_cap( 'delete_roles' );
        $role->add_cap( 'edit_roles'   );
      }
    }
		//Xoá các role mặc định
    self::remove_default_roles();
    //Thêm các role cần thiết cho trang web tin tức
    self::add_new_roles();
  }

  public static function remove_default_roles(){
    remove_role( 'subscriber' );
    remove_role( 'contributor' );
    remove_role( 'author' );
    remove_role( 'editor' );
  }

  public static function add_new_roles(){
    add_role( 'technician', 'Kỹ thuật viên', Technician_Caps );
    add_role( 'user-manager', 'Quản lý người dùng', User_Manager_Caps );
    add_role( 'editor', 'Biên tập viên', Editor_Caps );
    add_role( 'author', 'Tác giả', Author_Caps );
    add_role( 'contributor', 'Cộng tác viên', Contributor_Caps );
    add_role( 'subscriber', 'Độc giả', Subscriber_Caps );
  }

  public static function deactivation() {
  }
}

function user_manager_plugin() {
	return User_Manager_Plugin::get_instance();
}

// Chạy plugin
user_manager_plugin();
