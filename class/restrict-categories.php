<?php

class Restrict_Categories {

	protected $plugin_name = 'restrict-roles-categories';

	protected $version = '1.0.0';

	private static $instance = null;

	private $cat_list = NULL;

	protected function __construct() {
	}

    private function __clone() {
    }

    private function __wakeup() {
    }

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new Restrict_Categories;

			if ( is_admin() ) {
				$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : false;

				if ( $post_type == false || $post_type == 'post' )
					add_action( 'admin_init', array( self::$instance, 'posts' ) );

				add_action( 'admin_init', array( self::$instance, 'init' ) );
				add_action( 'admin_menu', array( self::$instance, 'add_admin' ) );

				add_filter( 'plugin_action_links', array( self::$instance, 'rc_plugin_action_links' ), 10, 2 );
				add_filter( 'screen_settings', array( self::$instance, 'add_screen_options' ) );

				add_action( 'admin_notices', array( self::$instance, 'admin_notices' ) );
			}

			if ( defined ( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
				add_action( 'xmlrpc_call', array( self::$instance, 'posts' ) );

		}
	}

	public function init() {
		register_setting(
			'RestrictCats_options_group',
			'RestrictCats_options',
			array(
				'santizie_callback' => array( $this, 'options_sanitize' ),
			)
		);
		register_setting(
			'RestrictCats_user_options_group',
			'RestrictCats_user_options',
			array(
				'santizie_callback' => array( $this, 'options_sanitize' ),
			)
		);

		add_option( 'RestrictCats_options' );
		add_option( 'RestrictCats_user_options' );

		$screen_options = get_option( 'RestrictCats-screen-options' );

		$defaults = array(
			'roles_per_page' => 20,
			'users_per_page' => 20
		);

		if ( !$screen_options )
			update_option( 'RestrictCats-screen-options', $defaults );

		if ( isset( $_POST['restrict-categories-screen-options-apply'] ) && in_array( $_POST['restrict-categories-screen-options-apply'], array( 'Apply', 'apply' ) ) ) {
			$roles_per_page = absint( $_REQUEST['RestrictCats-screen-options']['roles_per_page'] );
			$users_per_page = absint( $_REQUEST['RestrictCats-screen-options']['users_per_page'] );

			$updated_options = array(
				'roles_per_page' => $roles_per_page,
				'users_per_page' => $users_per_page
			);

			update_option( 'RestrictCats-screen-options', $updated_options );
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'restrict-categories' ) {
			if ( !isset( $_POST['action'] ) )
				return;

			if ( 'reset' !== $_POST['action'] )
				return;

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'rc-reset-nonce' ) )
				wp_die( __( 'Security check', 'restrict-categories' ) );

			update_option( 'RestrictCats_options', array() );
			update_option( 'RestrictCats_user_options', array() );
		}
	}


	public function admin_notices(){
		if ( !isset( $_POST['action'] ) )
			return;

		if ( isset( $_GET['page'] ) && $_GET['page'] !== 'restrict-categories' )
			return;

		switch( $_POST['action'] ) :
			case 'reset' :
				echo '<div id="message" class="updated"><p>' . __( 'Danh mục đã được thiết lập' , 'restrict-categories') . '</p></div>';
			break;
		endswitch;
	}

	public function admin_scripts() {
		wp_enqueue_script( 'restrict-categories-admin', plugins_url( '/js/restrict-categories.js', __FILE__ ), array( 'jquery' ), false, true );
	}

	public function rc_plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename(__FILE__) )
			$links[] = '<a href="options-general.php?page=restrict-categories">' . __( 'Settings', 'restrict-categories' ) . '</a>';

		return $links;
	}


	public function get_cats(){
		$cat = array();

		$categories = get_terms( 'category','hide_empty=0' );

		foreach ( $categories as $category ) {
			$cat[] = array(
				'slug' => $category->slug
			);
		}

		return $cat;
	}

	public function populate_opts(){
		$rc_options = array();

		$roles 	= $this->get_roles();
		$cats 	= $this->get_cats();

		foreach ( $roles as $name => $id ) {
			$rc_options[] = array(
				'role'			=> $id,
				'name'      => $name,
				'id'        => "{$id}_cats",
				'options'   => $cats
			);
		}

		return $rc_options;
	}

	public function populate_user_opts() {
		$rc_user_options = array();

		$logins	= $this->get_logins();
		$cats 	= $this->get_cats();

		foreach ( $logins as $name => $id ) {
			$rc_user_options[] = array(
				'name'     => $name,
				'id'       => "{$id}_user_cats",
				'options'  => $cats
			);
		}

		return $rc_user_options;
	}

	public function get_roles() {
		$roles = array();

		$editable_roles = get_editable_roles();

		foreach ( $editable_roles as $role => $name ) {
			$roles[ $name['name'] ] = $role;
		}

		return $roles;
	}

	public function get_logins(){
		$users = array();

		$args = array();

		if ( isset( $_POST['rc-search-users'] ) ) {
			$search = ( isset( $_REQUEST['rc-search'] ) && !empty( $_REQUEST['rc-search'] ) ) ? esc_html( $_POST['rc-search'] ) : '';
			$args = array( 'search' => $search );
		}

		$blogusers = get_users( $args );

		foreach ( $blogusers as $login ) {
			$users[ $login->user_login ] = $login->user_nicename;
		}

		return $users;
	}

	public function add_screen_options( $current ){
		global $current_screen;

		$options = get_option( 'RestrictCats-screen-options' );

		if ( $current_screen->id == 'settings_page_restrict-categories' ){
			$current = '<h5>Tuỳ chọn hiển thị</h5>
					<input type="text" value="' . $options['roles_per_page'] . '" maxlength="3" id="restrict-categories-roles-per-page" name="RestrictCats-screen-options[roles_per_page]" class="screen-per-page"> <label for="restrict-categories-roles-per-page">Quyền</label>
					<input type="text" value="' . $options['users_per_page'] . '" maxlength="3" id="restrict-categories-users-per-page" name="RestrictCats-screen-options[users_per_page]" class="screen-per-page"> <label for="restrict-categories-users-per-page">Người dùng</label>
					<input type="submit" value="Lưu" class="button" id="restrict-categories-screen-options-apply" name="restrict-categories-screen-options-apply">';
		}

		return $current;
	}

	public function options_sanitize( $input ){
		if ( !isset( $_POST['option_page'] ) )
			return;

		$options = 'RestrictCats_user_options_group' == $_POST['option_page'] ? get_option( 'RestrictCats_user_options' ) : get_option( 'RestrictCats_options' );

		if ( is_array( $input ) ) {
			foreach( $input as $k => $v ) {
				$options[ $k ] = $v;
			}
		}

		return $options;
	}

	public function add_admin() {
		$current_page = add_options_page( __('Phân quyền người dùng', 'restrict-categories'), __('Phân quyền người dùng', 'restrict-categories'), 'manage_categories', 'restrict-categories', array( $this, 'admin' ) );

		add_action( "load-$current_page", array( $this, 'admin_scripts' ) );
	}

	
	public function admin() {

		$tab = 'roles';

		if ( isset( $_GET['type'] ) && $_GET['type'] == 'users' )
			$tab = 'users';

		$roles_tab = esc_url( admin_url( 'options-general.php?page=restrict-categories' ) );
		$users_tab = add_query_arg( 'type', 'users', $roles_tab );
	?>

		<div class="wrap">
			<h2>
			<?php
				_e('Phân quyền người dùng', 'restrict-categories');
			?>
			</h2>

			<?php
                $boxes = new RestrictCats_User_Role_Boxes();

                if ( $tab == 'roles' ) :

                	$rc_options = $this->populate_opts();

            ?>
            	<form method="post" action="options.php">
	                <fieldset>
	                    <?php
	                    	settings_fields( 'RestrictCats_options_group' );

	                        $boxes->start_box( get_option( 'RestrictCats_options' ), $rc_options, 'RestrictCats_options' );
	                    ?>
	                </fieldset>
	                <?php submit_button(); ?>
            	</form>
			<?php
				elseif ( $tab == 'users' ) :

					$rc_user_options = $this->populate_user_opts();
            ?>
            	<form method="post" action="options-general.php?page=restrict-categories&type=users">
            		<fieldset>
						<p><?php _e( 'Chọn danh mục cho người dùng sẽ <em>ghi đè</em> lên danh mục bạn đã chọn cho quyền của người dùng đó.', 'restrict-categories' ); ?></p>
						<p>
							<input type="search" id="rc-search-users" name="rc-search" value="">
							<?php submit_button( __( 'Tìm kiếm người dùng', 'restrict-categories' ), 'secondary', 'rc-search-users', false ); ?>
						</p>
            		</fieldset>
				</form>

				<form method="post" action="options.php">
	                <fieldset>
	                    <?php
	                    	settings_fields( 'RestrictCats_user_options_group' );

	                        $boxes->start_box( get_option( 'RestrictCats_user_options' ), $rc_user_options, 'RestrictCats_user_options' );
	                    ?>
	                </fieldset>
	                <?php submit_button(); ?>
                </form>
                <?php endif; ?>

            <h3><?php _e('Cài đặt lại về mặc định	', 'restrict-categories'); ?></h3>
			<form method="post">
				<?php submit_button( __( 'Cài đặt lại', 'restrict-categories' ), 'secondary', 'reset' ); ?>
                <input type="hidden" name="action" value="reset" />
                <?php wp_nonce_field( 'rc-reset-nonce' ); ?>
			</form>
		</div>
	<?php

	}

	
	public function posts() {
		global $wp_query, $current_user;

		$defaults = array( 'RestrictCategoriesDefault' );

		$user = new WP_User( $current_user->ID );

		$user_cap = $user->roles;

		if ( function_exists( 'get_users' ) )
			$user_login = $user->user_nicename;
		elseif ( function_exists( 'get_users_of_blog' ) )
			$user_login = $user->ID;

		$settings = get_option( 'RestrictCats_options' );

		$settings_user = get_option( 'RestrictCats_user_options' );

		if ( is_array( $settings_user ) && array_key_exists( $user_login . '_user_cats', $settings_user ) )
			$settings_user[ $user_login . '_user_cats' ] = array_values( array_diff( $settings_user[ $user_login . '_user_cats' ], $defaults ) );

		if ( is_array( $settings_user ) && !empty( $settings_user[ $user_login . '_user_cats' ] ) ) {

			foreach ( $settings_user[ $user_login . '_user_cats' ] as $category ) {
				$term_id = get_term_by( 'slug', $category, 'category' )->term_id;

				if ( function_exists( 'icl_object_id' ) )
					$term_id = icl_object_id( $term_id, 'category', true );

				$this->cat_list .= $term_id . ',';
			}

			$this->cat_filters( $this->cat_list );
		}
		else {
			foreach ( $user_cap as $key ) {
				if ( is_array( $settings ) && !empty( $settings[ $key . '_cats' ] ) ) {
					$settings[ $key . '_cats' ] = array_values( array_diff( $settings[ $key . '_cats' ], $defaults ) );

					foreach ( $settings[ $key . '_cats' ] as $category ) {
						$term_id = get_term_by( 'slug', $category, 'category' )->term_id;

						if ( function_exists( 'icl_object_id' ) )
							$term_id = icl_object_id( $term_id, 'category', true );

						$this->cat_list .= $term_id . ',';
					}
				}

				$this->cat_filters( $this->cat_list );
			}
		}
	}


	public function cat_filters( $categories ){
		$this->cat_list = rtrim( $categories, ',' );

		if ( empty( $this->cat_list ) )
			return;

		global $pagenow;

		if ( $pagenow == 'edit.php' || ( defined ( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) )
			add_filter( 'pre_get_posts', array( $this, 'posts_query' ) );

		$pages = array( 'edit.php', 'post-new.php', 'post.php' );

		if ( in_array( $pagenow, $pages ) || ( $pagenow == 'edit-tags.php' && $_GET['taxonomy'] == 'category' ) || ( defined ( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) )
			add_filter( 'list_terms_exclusions', array( $this, 'exclusions' ) );
	}

	
	public function posts_query( $query ){
		if ( $this->cat_list !== '' ) {
			$cat_list_array = explode( ',', $this->cat_list );

			if ( ! isset( $_REQUEST['cat'] ) )
				$query->set( 'category__in', $cat_list_array );
			elseif( isset( $_REQUEST['cat'] ) && $_REQUEST['cat'] == '0' )
				$query->set( 'category__in', $cat_list_array );
		}

		return $query;
	}


	public function exclusions(){
		$excluded = " AND ( t.term_id IN ( $this->cat_list ) OR tt.taxonomy NOT IN ( 'category' ) )";

		return $excluded;
	}
}


class RestrictCats_User_Role_Boxes {

	var $_pagination_args = array();

	public function start_box( $settings, $options, $options_name ) {
		$walker = new RestrictCats_Walker_Category_Checklist();

		$screen_options = get_option( 'RestrictCats-screen-options' );

		$per_page = 'RestrictCats_options' == $options_name ? $screen_options['roles_per_page'] : $screen_options['users_per_page'];

		$current_page = $this->get_pagenum();

		$total_items = count( $options );

		$options = array_slice( $options, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );

		echo '<div class="tablenav">';
			$this->pagination( 'top' );
		echo '<br class="clear" /></div>';

		foreach ( $options as $key => $value ) :

			$id = $value['id'];
			$role = get_role($value['role']);

			if ( isset( $settings[ $id ] ) && is_array( $settings[ $id ] ) )
				$selected = $settings[ $id ];
			else
				$selected = array();

			$roles_tab = esc_url( admin_url( 'options-general.php?page=restrict-categories' ) );
			$users_tab = add_query_arg( $id . '-tab', 'popular', $roles_tab );

			if ( isset( $_REQUEST['type'] ) && $_REQUEST['type'] == 'users' ) {
				$roles_tab = add_query_arg( array( 'type' => 'users', $id . '-tab' => 'all' ), $roles_tab );
				$users_tab = add_query_arg( array( 'type' => 'users', $id . '-tab' => 'popular' ), $roles_tab );
			}

			if ( isset( $_REQUEST['paged'] ) ) {
				$roles_tab = add_query_arg( array( 'paged' => absint( $_REQUEST['paged'] ) ), $roles_tab );
				$users_tab = add_query_arg( array( 'paged' => absint( $_REQUEST['paged'] ) ), $users_tab );
			}

			$current_tab = 'all';

			if ( isset( $_REQUEST[ $id . '-tab' ] ) && in_array( $_REQUEST[ $id . '-tab' ], array( 'all', 'popular' ) ) )
				$current_tab = $_REQUEST[ $id . '-tab' ];
		?>
			<div id="side-sortables" class="metabox-holder" style="float:left; padding:5px;">
				<div class="postbox">
					<h3 class="hndle"><span><?php echo $value['name']; ?></span></h3>
	                <div class="inside" style="padding:0 10px;">
						<div class="taxonomydiv">
							<div id="<?php echo $id; ?>-all" class="tabs-panel <?php echo ( 'all' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' ); ?>">
								<ul class="categorychecklist form-no-clear">
								<?php
									wp_list_categories(
										array(
										'admin'          => $id,
										'selected_cats'  => $selected,
										'options_name'   => $options_name,
										'hide_empty'     => 0,
										'title_li'       => '',
										'disabled'       => ( 'all' == $current_tab ? false : true ),
										'walker'         => $walker
										)
									);

									$disable_checkbox = ( 'all' == $current_tab ) ? '' : 'disabled="disabled"';
								?>
	                            <input style="display:none;" <?php echo $disable_checkbox; ?> type="checkbox" value="RestrictCategoriesDefault" checked="checked" name="<?php echo $options_name; ?>[<?php echo $id; ?>][]">
								</ul>
							</div>
	                        <div id="<?php echo $id; ?>-popular" class="tabs-panel <?php echo ( 'popular' == $current_tab ? 'tabs-panel-active' : 'tabs-panel-inactive' ); ?>">
	                        	<ul class="categorychecklist form-no-clear">
								<?php
									wp_list_categories(
										array(
										'admin'          => $id,
										'selected_cats'  => $selected,
										'options_name'   => $options_name,
										'hide_empty'     => 0,
										'title_li'       => '',
										'orderby'        => 'count',
										'order'          => 'DESC',
										'disabled'       => ( 'popular' == $current_tab ? false : true ),
										'walker'         => $walker
										)
									);

									$disable_checkbox = ( 'popular' == $current_tab ) ? '' : 'disabled="disabled"';
								?>
	                            <input style="display:none;" <?php echo $disable_checkbox; ?> type="checkbox" value="RestrictCategoriesDefault" checked="checked" name="<?php echo $options_name; ?>[<?php echo $id; ?>][]">
								</ul>
							</div>
						</div>

	                    <?php
							$shift_default = array_diff( $selected, array( 'RestrictCategoriesDefault' ) );
							$selected      = array_values( $shift_default );
						?>
						<p style="padding-left:10px;">
							<strong><?php echo count( $selected ); ?></strong> <?php echo ( count( $selected ) > 1 || count( $selected ) == 0 ) ? 'mục' : 'mục'; ?> được chọn
							<span class="list-controls" style="float:right; margin-top: 0;">
								<a class="select-all" id="<?php echo $id; ?>-select-all" href="#"><?php _e( 'Chọn tất cả', 'restrict-categories' ); ?></a>
							</span>
						</p>

					</div>
				</div>
			</div>
		<?php
		endforeach;
	}

	protected function get_pagenum() {
		$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

		if( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] )
			$pagenum = $this->_pagination_args['total_pages'];

		return max( 1, $pagenum );
	}

	protected function get_items_per_page( $option, $default = 20 ) {
		$per_page = (int) get_user_option( $option );
		if ( empty( $per_page ) || $per_page < 1 )
			$per_page = $default;

		return (int) apply_filters( $option, $per_page );
	}

	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		$output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s mục', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = $disable_last = $disable_prev = $disable_next = false;

 		if ( $current == 1 ) {
			$disable_first = true;
			$disable_prev = true;
 		}
		if ( $current == 2 ) {
			$disable_first = true;
		}
 		if ( $current == $total_pages ) {
			$disable_last = true;
			$disable_next = true;
 		}
		if ( $current == $total_pages - 1 ) {
			$disable_last = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				__( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				__( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	protected function set_pagination_args( $args ) {
		$args = wp_parse_args( $args, array(
			'total_items' => 0,
			'total_pages' => 0,
			'per_page' => 0,
		) );

		if ( !$args['total_pages'] && $args['per_page'] > 0 )
			$args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );

		if ( ! headers_sent() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) && $args['total_pages'] > 0 && $this->get_pagenum() > $args['total_pages'] ) {
			wp_redirect( add_query_arg( 'paged', $args['total_pages'] ) );
			exit;
		}

		$this->_pagination_args = $args;
	}
}

class RestrictCats_Walker_Category_Checklist extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); 

	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el( &$output, $category, $depth = 0, $args = array(), $current_object_id = 0 ) {
		extract($args);

		if ( empty( $taxonomy ) )
			$taxonomy = 'category';

		$output .= sprintf(
			'<li id="%4$s-category-%1$d"><label class="selectit"><input value="%2$s" type="checkbox" name="%3$s[%4$s][]" %5$s %6$s /> %7$s</label>',
			$category->term_id,
			$category->slug,
			$options_name,
			$admin,
			checked( in_array( $category->slug, $selected_cats ), true, false ),
			( $disabled === true ? 'disabled="disabled"' : '' ),
			esc_html( apply_filters( 'the_category', $category->name ) )
		);
	}

	function end_el( &$output, $category, $depth = 0, $args= array() ) {
		$output .= "</li>\n";
	}
}


function restrict_categories_plugin_instance() {

	return Restrict_Categories::instance();
}

restrict_categories_plugin_instance();
