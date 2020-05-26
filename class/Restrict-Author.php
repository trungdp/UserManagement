<?php
// final class Restrict_Author{
// 	private static $instance;
// 	function __construct(){ }
//
// 	public static function get_instance() {
//     if ( ! self::$instance ){
//       self::$instance = new self;
//       self::$instance->setup_action();
//     }
//
//   	return self::$instance;
//   }
//
//   private function setup_action(){
//     add_action( 'show_user_profile',  array( $this, 'restrict_user_form'));
//     add_action( 'user_new_form',      array( $this, 'restrict_user_form'));
//     add_action( 'edit_user_profile',  array( $this, 'restrict_user_form'));
//
//     add_action( 'personal_options_update',  array( $this, 'restrict_save_data'));
//     add_action( 'edit_user_profile_update', array( $this, 'restrict_save_data'));
//
//     add_action( 'save_post', array( $this, 'save_restrict_post'));
//     add_action( 'edit_form_after_title', array( $this,'restrict_warning'));
// //     add_action( 'admin_menu', array( $this,'restrict_remove_meta_boxes'));
//   }
//
//
// }
//
// Restrict_Author::get_instance();


add_action( 'show_user_profile',  'restrict_user_form');
add_action( 'user_new_form',      'restrict_user_form');
add_action( 'edit_user_profile',  'restrict_user_form');

add_action( 'personal_options_update',  'restrict_save_data');
add_action( 'edit_user_profile_update',  'restrict_save_data');

add_action( 'save_post', 'save_restrict_post');
add_action( 'edit_form_after_title', 'restrict_warning');
function restrict_user_form( $user ) {
  $args = array(
    'show_option_all'    => '',
    'show_option_none'   => '',
    'orderby'            => 'ID',
    'order'              => 'ASC',
    'show_count'         => 0,
    'hide_empty'         => 0,
    'child_of'           => 0,
    'exclude'            => '',
    'echo'               => 1,
    'selected'           => get_user_meta( $user->ID, '_access', true),
    'hierarchical'       => 0,
    'name'               => 'allow',
    'id'                 => '',
    'class'              => 'postform',
    'depth'              => 0,
    'tab_index'          => 0,
    'taxonomy'           => 'category',
    'hide_if_empty'      => false,
    'walker'             => ''
  );
  $user = wp_get_current_user();
  if ( in_array( 'administrator', (array) $user->roles ) || in_array( 'user-manager', (array) $user->roles ) ) {
    ?>
      <h3>Restrict Author Post to a category</h3>
      <table class="form-table">
        <tr>
          <th><label for="access">Writing to:</label></th>
          <td>
            <?php wp_dropdown_categories($args); ?>
            <br />
            <span class="description">Use to restrict an author posting to just one category.</span>
          </td>
        </tr>
      </table>
    <?php
  }
}

 function restrict_save_data( $user_id ) {
  if ( !current_user_can( 'administrator', $user_id ) )
    return false;
  update_user_meta( $user_id, '_access', $_POST['allow'] );
}

// Kiểm tra ngừoi dùng đăng nhập vào là tác gỉa và bị hạn chế viết bài
 function is_restrict() {
  return get_user_meta(get_current_user_id(), '_access', true) != '';
}

function is_writter(){
  $user = wp_get_current_user();
  if ( in_array( 'author', (array) $user->roles ) ) {
    //The user has the "author" role
  }
}

/* auto register category to post that the author's being restricted */
 function save_restrict_post( $post_id ) {
  if ( ! wp_is_post_revision( $post_id ) && is_restrict() ){
    remove_action('save_post', 'save_restrict_post');
    wp_set_post_categories( $post_id, get_user_meta( get_current_user_id() , '_access', true) );
    add_action('save_post', 'save_restrict_post');
  }
}

//Cảnh báo tác giả
 function restrict_warning( $post_data = false ) {
  if (is_restrict()) {
    $c = get_user_meta( get_current_user_id() , '_access', true);
    $data = get_category($c);
    echo 'You are allowing to post to category: <strong>'. $data->name .'</strong><br /><br />';
  }
}

/* remove category dropdown box in editor */
 function restrict_remove_meta_boxes() {
  if (is_restrict() )
    remove_meta_box('categorydiv', 'post', 'normal');
}