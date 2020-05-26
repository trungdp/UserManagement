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

include 'define.php';

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
//       // General admin functions.
      require( plugin_dir_path( __FILE__ ) . '/class/Restrict-Author.php' );
      require( plugin_dir_path( __FILE__ ) . '/class/restrict-categories.php' );
//
//       // Plugin settings.
//       require_once( $this->dir . 'admin/class-settings.php' );
//
//       // User management.
//       require_once( $this->dir . 'admin/class-manage-users.php' );
//       require_once( $this->dir . 'admin/class-user-edit.php'    );
//       require_once( $this->dir . 'admin/class-user-new.php'     );
//
//       // Edit posts.
//       require_once( $this->dir . 'admin/class-meta-box-content-permissions.php' );
//
//       // Role management.
//       require_once( $this->dir . 'admin/class-manage-roles.php'          );
//       require_once( $this->dir . 'admin/class-roles.php'                 );
//       require_once( $this->dir . 'admin/class-role-edit.php'             );
//       require_once( $this->dir . 'admin/class-role-new.php'              );
//       require_once( $this->dir . 'admin/class-meta-box-publish-role.php' );
//       require_once( $this->dir . 'admin/class-meta-box-custom-cap.php'   );
//
//       // Edit capabilities tabs and groups.
//       require_once( $this->dir . 'admin/class-cap-tabs.php'       );
//       require_once( $this->dir . 'admin/class-cap-section.php'    );
//       require_once( $this->dir . 'admin/class-cap-control.php'    );
    }
  }

  private function setup_actions() {
    register_activation_hook( __FILE__, array( $this, 'activation' ) );
    register_deactivation_hook( __FILE__, array($this, 'deactivation'));
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
