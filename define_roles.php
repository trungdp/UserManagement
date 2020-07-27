<?php 

//File define_role chứa các khai báo role cần thiết cho một trang web tin tức, 
//dùng hàm define(<name>,<value>) để khai báo, giá trị 'name' được khai báo
//là một mảng các capability 'value'

define('Author_Caps',array( 
  'read' => true,
  'edit_posts' => true,
  'edit_others_posts'=>true,
  'delete_posts'=>true,
  'delete_published_posts'=>true,
  'edit_published_posts'=>true,
  'upload_files'=>true,
  'level_0' => true ));
define('Contributor_Caps',array( 
  'read' => true,
  'edit_posts' => true,
  'delete_posts'=>true,
  'level_0' => true ));
define('Editor_Caps',array( 
  'read' => true,
  'edit_posts' => true,
  'edit_others_posts'=>true,
  'edit_private_posts'=>true,
  'edit_published_posts'=>true,
  'publish_posts'=>true,
  'read_private_posts'=>true,
  'delete_posts'=>true,
  'delete_private_posts'=>true,
  'delete_published_posts'=>true,
  'delete_others_posts'=>true,
  'edit_pages' => true,
  'edit_others_pages'=>true,
  'publish_pages'=>true,
  'read_private_pages'=>true,
  'delete_pages'=>true,
  'delete_private_pages'=>true,
  'delete_published_pages'=>true,
  'delete_others_pages'=>true,
  'edit_private_pages'=>true,
  'edit_published_pages'=>true,
  'upload_files'=>true,
  'manage_links'=>true,
  'moderate-comments'=>true,
  'unfiltered-html'=>true,
  'manage_categories'=>true,
  'level_0' => true ));
define('Subscriber_Caps',array( 
  'read' => true,
  'level_0' => true ));
define('User_Manager_Caps',array( 
  'read' => true,
  'create_roles'=>true,
  'delete_roles'=>true,
  'edit_roles'=>true,
  'list_roles'=>true,
  'create_users'=>true,
  'delete_users'=>true,
  'edit_users'=>true,
  'list_users'=>true,
  'promote_users'=>true,
  'remove_users'=>true,
  'level_0' => true ));
define('Technician_Caps',array( 
  'read' => true,
  'upload_files'=>true,
  //blocks
  'publish_blocks'=>true,
  'read_private_blocks'=>true,
  'delete_blocks'=>true,
  'delete_private_blocks'=>true,
  'edit_private_blocks'=>true,
  //themes
  'delete_themes'=>true,
  'edit_themes_options'=>true,
  'edit_themes'=>true,
  'install_themes'=>true,
  'switch_themes'=>true,
  'update_themes'=>true,
  //plugins
  'activate_plugins'=>true,
  'delete_plugins'=>true,
  'edit_plugins'=>true,
  'install_plugins'=>true,
  'update_plugins'=>true,
  'level_0' => true ));