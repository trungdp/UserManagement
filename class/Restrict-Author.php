<?php

// File Restrict_Author chứa các hàm để thực hiện chức năng ngăn cản ngừoi dùng 
// đăng bài vào các danh mục không được chỉ định, ý tưởng là sẽ thêm một field 
// vào user meta. Chỉ những role như admin hay user-manager mới có thể nhìn thấy
// và chỉnh sửa field này ở trang user profile. Ở trang Edit, người viết có thể vẫn 
// nhìn thấy nhứng danh mục khác, nhưng restrict_save_data, người viết chỉ có thể 
// đăng bài trong danh mục được chỉ định.

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
      <h3>Được phép đăng bài trong danh mục:</h3>
      <table class="form-table">
        <tr>
          <th><label for="access">Danh mục:</label></th>
          <td>
            <?php wp_dropdown_categories($args); ?>
            <br />
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

 function save_restrict_post( $post_id ) {
  if ( ! wp_is_post_revision( $post_id ) && is_restrict() ){
    remove_action('save_post', 'save_restrict_post');
    wp_set_post_categories( $post_id, get_user_meta( get_current_user_id() , '_access', true) );
    add_action('save_post', 'save_restrict_post');
  }
}

//Cảnh báo tác giả
 function restrict_warning() {
  echo "<script>console.log('run restrict_warning' );</script>";
  if (is_restrict()) {
    $c = get_user_meta( get_current_user_id() , '_access', true);
    $data = get_category($c);
    echo 'You are allowing to post to category: <strong>'. $data->name .'</strong><br /><br />';
  }
}

 function restrict_remove_meta_boxes() {
  if (is_restrict() )
    remove_meta_box('categorydiv', 'post', 'normal');
}

add_action( 'edit_form_after_title', 'restrict_warning');
add_action( 'show_user_profile',  'restrict_user_form');
add_action( 'user_new_form',      'restrict_user_form');
add_action( 'edit_user_profile',  'restrict_user_form');

add_action( 'personal_options_update',  'restrict_save_data');
add_action( 'edit_user_profile_update',  'restrict_save_data');

add_action( 'save_post', 'save_restrict_post');


add_action('admin_menu', 'restrict_remove_meta_boxes');