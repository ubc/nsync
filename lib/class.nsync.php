<?php 

/**
* This is tne main Nsnyc plugin class. Handles adding or removing network sites to a list of allowed content pushing sites. 
*
* @author     ctlt
* @version    1.1
*/

class Nsync {
	
	//global variables.
	static $settings = array();
	static $currently_publishing = false;
	static $post_from = array();
	static $default_source_template = "source: <a href='{post permalink}'>{post title}</a>";
	
	/**
	 * Initialize wordpress administration functionality.
	 */
	public static function admin_init() {
		
		self::$settings = get_option( 'nsync_options' );
		
		register_setting(
			'writing', // settings page
			'nsync_options', // option name
			array( 'Nsync', 'validate') // validation callback
		);
		
		add_settings_field(
			'nsync_options', // id
			'Network Sites Publishers', // setting title
			array( 'Nsync', 'add_network_sites'), // display callback
			'writing', // settings page
			'remote_publishing' // settings section
		);
		
		// register scipts and styles
		wp_register_script( 'nsync-ui', NSYNC_DIR_URL . '/js/ui.js');
		wp_register_script( 'nsync-add-site', NSYNC_DIR_URL."/js/add-site.js");
		wp_register_style( 'nsync-post-writing', NSYNC_DIR_URL."/css/writing.css", null, '1.0', 'screen' );
		
	}
	
   /**
	* Compile the administration modules and UI.
	*/
	public static function add_network_sites() {
		
		global $current_user;
		
		$current_blog_id = get_current_blog_id();
		
		$active = null;
		if (isset(self::$settings['active'])) {
			$active = self::$settings['active'];
		} 
		
		// The admin tab navigation.
		echo "<div id='nsync-nav'><div id='current'> <p>Current</p> </div> <div id='add'> <p>Add Sites</p> </div><div id='general'> <p>General</p> </div></div>";
		
		// add current publishing sites module.
		echo "<div class='current nsync'>";
		echo self::createPublishingList( $active );
		echo "</div>";
		
		// add modules for adding sites to push list.
		echo "<div class='add nsync'>";
		echo self::createSearchBox();
		echo self::createExternalPublishing( $current_user );
		echo self::createUserList( $current_user );
		echo "</div>";
		
		// add modules for general post settings.
		echo "<div class='general nsync'>";
		echo self::createPostCategory();
		echo self::createPostInformation();
		echo "</div>";
		
	}
	
   /**
	* Creates the view for choosing a category to autmatically be attached to pushed posts.
	*
	* @return string     html of the module to be printed.
	*/
	public static function createPostCategory(  ) {

		$html = '';

		$drop_down = array (
						"hierarchical" => 1,
						"name" => "nsync_options[category]",
						"selected" => self::$settings['category'],
						'echo' => 0,
						'show_option_none' => 'None' 
					 );
		
		$html .= '<p><label>Select Category: ' . wp_dropdown_categories( $drop_down ) . '</label></p>';
		$html .= '<p><em> This Category is added to the pushed posts by default.</em></p>';

		return self::wrapModule ( "Category for Nsync", $html );
	}

   /**
	* Creates the view for general settings on pushed posts.
	*
	* @return string     html of the module to be printed.
	*/
	public static function createPostInformation(  ) {
		
		// array is for storing all information to create input checkboxes.
		$post_settings = [ 
			'duplicate_files' => "Copy files that are attached to the post to this site.",
			'force_user' => "Only Authors, Editor and Administrators are able to remotely publish to this site.",
			'include_new_cats_tags' => "Restrict source post's categories to ones already on your blog.",
			'link_to_source' => "Make post title permalink go to original source.",
			'source_before' => "Show source of post before displaying content.",
			'source_after' => "Show source of post after displaying content."	
		];	

		$post_status_setting = [
			'0' => 'Inherit from the post',
			'draft' => 'Draft',
			'publish' => 'Publish',
			'pending' => 'Pending' 
		];

		$html .= '<p><label>Select the Post Status
				  <select name="nsync_options[post_status]">';

		// Let the user check the post
		$post_status = self::$settings['post_status'];

		foreach ($post_status_setting as $value => $text) {
			
			$selected = selected( $post_status, $value, 0 );
			$html .= '<option value="' . $value . '" ' . $selected  . '>' . $text . '</option>';
		}

		$html .= '</select></label></p>';			
			
		// force check to only users that are also members of this blog. 
		foreach ($post_settings as $input => $msg) {

			$checked =  checked( ( isset( self::$settings[ $input ] ) && self::$settings[ $input ]), true, 0);
			$html .=	'<p>
							<label>
								<input type="checkbox" name="nsync_options[' . $input . ']" value="1" ' . $checked . ' /> 
								' . $msg . '
							</label>
					    </p>';
		}		
		
		$value  = (isset(self::$settings['source_template'])? self::$settings['source_template'] : Nsync::$default_source_template); 
		$checked = checked( (isset(self::$settings['source_template']) && self::$settings['source_template'] ), true, 0);
		$html .= '<p><br>
					<label> 
						Template used to display source: 
					  	<input type="text" name="nsync_options[source_template]" value="' . $value . '" ' . $checked .' class="regular-text" /> 
				  	</label>
				  </p>';

		$html .= '<p>
				  	<em>Valid tags include: {site permalink}, {site name}, {post permalink}, {post date}, {post title}, {post author}</em>
				 </p>
				 </div>';

		return self::wrapModule ( "Post Information", $html );
	}
	
   /**
	* Creates module that shows the list of sites that belong to the current admin.
	*
	* @param object  $current_user    the current user's information.
	* 
	* @return string     html of the module to be printed.
	*/
	public static function createExternalPublishing( $current_user ) {

		$html  = ''; 

		if ( $current_user->ID ) {
			$user_blogs = get_blogs_of_user( $current_user->ID );

			if ( is_array( $user_blogs ) ) { 

			   $html  .=  "<p class='list-title'>Select from user " . $current_user->user_nicename . " current sites, that you want to enabled the external publishing from.</p> ";
				
				foreach ( $user_blogs as $blog ) {
					
					if ( $current_blog_id != $blog->userblog_id ) {
						$html  .= '<div class="publishing-sites">
										<label> 
											<input name="nsync_options[active][]" type="checkbox" value="' . esc_attr( $blog->userblog_id ) . '" /> ' . $blog->blogname . 
										'</label> 
										<p><small>' . $blog->siteurl . '</small></p>
									</div>';
						$showen[] = $blog->userblog_id; 
					}
				}
			} 
		} 

		return self::wrapModule ( "Avaliable Publishing Sites", $html );
	}
	
   /**
	* Creates module that shows what sites are currently pushing to your site.
	*
	* @param  array  $active      the list of active sites pushing to site.
	* 
	* @return string   html of the module to be printed.
	*/
	public static function createPublishingList( $active ) {

		$html  = ''; 
		
		if ( is_array($active) && !empty($active)) {

			$html .= '<p class="list-title">Selected sites are able to publish content.</p>';
	
			foreach ( $active as $active_site_id ) {

				$details = get_blog_details( array( 'blog_id' => $active_site_id ) );
				
				if ( ( ! $details->archived && ! $details->spam && ! $details->deleted ) && $active_site_id != $current_blog_id ) {
					
					$html .= '<div class="publishing-sites">
							  	<label>
									<input name="nsync_options[active][]" type="checkbox" checked="checked" value="' . esc_attr( $active_site_id ) . '" />'
							     . $details->blogname . ' <small>' . $details->siteurl . '</small>
								</label>
							 </div>';
				}
			}

		} else {
	
			$html .= '<p><em>There are currently no external sites that are able to publish content on your site.</em></p><br>';
		}
		
		return self::wrapModule ( "Current Publishing Sites", $html );
	}
	
   /**
	* Creates module that shows the users and their associated network sites.
	*
	* @param  object  $current_user      the current user's information.
	*
	* @return string   html of the module to be printed.
	*/
	public static function createUserList( $current_user ) {

	    // all the users sites 
		$args = array( 'who' => 'all' );
		unset($blog);
		$all_blogs = null;
		$showen[] = $current_blog_id; 
		$users = get_users( $args );
		$html = '';
		
		// limit 
		(int)$pagenum = ( isset( $_GET['num'] ) ? $_GET['num'] : 1 );
		
		$per_page = 10;
		$total_numer_of_users = count($users);
		
		$users = array_slice( $users, intval( ( $pagenum - 1 ) * $per_page ), intval( $per_page ) );

		if (sizeof($users) > 1 || $current_user->ID != $users[0]->ID) { 

			// todo: this might exlode if there are many users. like on a site that has all the 10 authors
			foreach ( $users as $user ) {
				
				if ( $current_user->ID != $user->ID && !$user->deleted ) { // don't add the current user blogs they are listed above anyways

					$user_blogs = get_blogs_of_user( $user->ID );

						if ( is_array( $user_blogs ) ) {

							$avatar_image = get_avatar( $user->email, 24 );	
							
							if ($user->last_name && $user->last_name) {
								$display_name = $user->first_name . ' ' . $user->last_name . '<em>(' . $user->display_name . ')</em>';
							} else {
								$display_name = $user->display_name;
							}
							
						
							$html .= '<div class="user-blogs">' . $avatar_image .
									 '<span class="display-name">' . $display_name . '</span>';
								
							$html .= '<div class="user-blogs-list">';
						
								foreach ( $user_blogs as $blog ) {
									
									if ( !in_array( $blog->userblog_id, $showen ) ) {
									
										$all_blogs[ $blog->userblog_id ] = $blog; // make the all_blogs unique
			
										$html .= '<div class="user-blogs-item">
														<label> 
															<input name="nsync_options[active][]" type="checkbox" value="' . esc_attr( $blog->userblog_id ) .'" /> 
															 ' . $blog->blogname . '
														</label> <a href="' . $blog->siteurl . '" target="_blank"><small>' . $blog->siteurl . '</small></a>
												   </div>';
									}
								}

								$html .= '</div> </div>';
							
						} // has any blogs?
						
				}
			}

			// handles pagincation based on how many users are found
			$number_of_pages = Nsync::paginate( $total_numer_of_users, $per_page , $pagenum , admin_url( 'options-writing.php' ) );
			$html .= '<p id="paginate">Pages : ' . $number_of_pages  . '</p>';

		}

		return self::wrapModule ( "User List", $html );
	}
	
	/**
	 * Creates module that allows user to search sites and group select results.
	 *
	 * @return string   html of the module to be printed.
	 */
	public static function createSearchBox() {

		if ( current_user_can('manage_sites') ) {
			
			$html = '<input type="text" id="nsync-site-search" class="regular-text" placeholder="url or site name " /> <div id="nsync-add-site"> Search </div>';
			$html .= "<h4>Search Results:</h4><div class='check'>uncheck all</div><div id='search-site-results'></div>";
			$html .= "<p><small>Only available to Super Admins</small></p>";
			
  		} 

  		return self::wrapModule ( "Search Sites", $html );
	}
	
   /**
	* Wraps html of each module into a consistant looking div
	*
	* @param  string  $text      title of module.
	* @param  string  $content   html to be displayed in module.
	* @param  string  $id        id to add to module.
	*
	* @return string  $html   html of the module to be printed.
	*/
	public static function wrapModule ($text, $content, $id = '') {
		
		$id_attr = '';

		if ($id != '') {
			$id_attr =  'id="' . $id . '"';
		} 

		$html = "<div " . $id_attr . " class='module'>";
		$html .= "<p class='module-title'> " . $text . "<p>";
		$html .= "<div>" . $content . "</div>";
		$html .= "</div>";

		return $html;
	}
	
   /**
	* Handles pagination logic for the user list module.
	*
	* @param  integer  $total          total amount of users found.
	* @param  integer  $per_page       how many users are shown each page.
	* @param  integer  $current_page   the current page location.
	* @param  string   $url_start       the base for new url. 
	*
	* @return string   list of page numbers.
	*/
	public static function paginate( $total, $per_page, $current_page = 1 , $url_start ) {
		
		$total_pages = ceil( $total / $per_page );
		for ( $page = 1; $page <= $total_pages ; $page++ ) {
			if ($current_page == $page)
				$list_pages[] = $page;	
			else
				$list_pages[] = '<a href="'.$url_start.'?num='.$page.'">'.$page.'</a>';	
		}

		return implode(" ", $list_pages );
	}
	
   /**
	* validation callback
	*
	* @param  array  $input  information on admin inputs
	*
	* @return array  $input  information on admin inputs
	*/
	public static function validate( $input ) {
	
		
		$current_blog_id = get_current_blog_id();
		
		if (isset($input['active']) && is_array( $input['active'] ) )
			$active = array_unique( $input['active'] );
		else
			$active = array();
		
		$to_remove = array();
		$to_add = array();
		
		if ( is_array( self::$settings['active'] ) && !empty( $active ) ):
			$intersect 	= array_intersect( self::$settings['active'], $active );
			
			$to_add 	= array_diff( $active, $intersect );
			
			$to_remove 	= array_diff( self::$settings['active'], $intersect );
			
		elseif ( is_array( self::$settings['active'] ) && empty( $active ) ):
			
			$to_remove 	= self::$settings['active'];
		
		else:
			$to_add 	= $active;
		endif;
		
		// remove sites
		if (!empty($to_remove)) {
			Nsync::remove_sites( $to_remove, $current_blog_id );
		}
		
		// add new sites 
		if (!empty($to_add)) {
			Nsync::add_sites( $to_add, $current_blog_id );
		}
		
		$input['active'] = $active;
		
		self::$settings = $input;
		
		return $input;
	}
	
   /**
	* Removes sites from active pushing list
	*
	* @param  array    $to_remove          list of blogs to remove from pushing list.
	* @param  integer  $current_blog_id    the id of the current network site.
	* 
	*/
	public static function remove_sites( $to_remove, $current_blog_id ) {
		
		// remove sites that you don't want any more.
		if( !empty( $to_remove ) ):
		
			foreach( $to_remove as $blog_id ):
				
				switch_to_blog( $blog_id );
				
				$post_to = get_option( 'nsync_post_to' );
				
				
				if( is_array($post_to) ):
				
					// there is nothing to remove if we don't have the $post_to set as an array
					$post_to = array_diff( $post_to , array( $current_blog_id ) );
					$post_to = array_unique($post_to);
					update_option( 'nsync_post_to', $post_to );
				
				endif;
				restore_current_blog();
				unset( $post_to ); 
				
			endforeach;
		endif;
		
	}
	
   /**
	* Add a site to the list of allowed publishing sites. 
	*
	* @param  array    $to_add               list of selected sites to add to the pushing list. 
	* @param  integer  $current_blog_id      the id of the current network site.
	*
	*/
	public static function add_sites( $to_add , $current_blog_id) {
	
		
		if( !empty( $to_add ) ):
			foreach( $to_add as $blog_id ):
				if( $blog_id != $current_blog_id ): // never add yourself to the its own blog to the list of blogs 	
					switch_to_blog( $blog_id );
					
					$post_to = get_option( 'nsync_post_to' );
					if( !is_array($post_to) )
						$post_to = array();
					$post_to[] = $current_blog_id;
					$post_to = array_unique($post_to);
					update_option( 'nsync_post_to', $post_to );
					
					restore_current_blog();
					unset( $post_to ); 
				endif;
			endforeach;
			
		endif;
	}
	
   /**
	* Enqueue all scripts if on the admin
	*
	*/
	public static function writing_script_n_style() {
		
		if ( current_user_can( 'manage_sites' ) ) {
		
			wp_enqueue_script( 'nsync-add-site' );
			wp_enqueue_script( 'nsync-ui' );
		}
		
		wp_enqueue_style( 'nsync-post-writing' );
	}
	
   /**
	* Handles triggered on ajax request made in add-site.js. 
	*
	*/
	public static function ajax_lookup_site() {
		
		if( !current_user_can( 'manage_sites' ) ):
			return "0";
			die();
		endif;
		
		$sites = Nsync::search_site( $_POST['term'] );
			
		foreach( $sites as $site )
			$results[] = array( 'label'=> $site['domain'].$site['path'] , 'value'=> $site['blog_id'] );
			
		echo json_encode( $results );
		die();

	}
	
   /**
	* Based on a requested search query the database for a list of sites. 
	* This used for the search module and is triggered by ajax.
	*
	* @param  string  $s   search query
	*
	* @return array  a list of sites that match the search. Includes the name of the site and url.
	*/
	public static function search_site( $s ) {
		
		global $wpdb;
		// copied most of it from class-wp-ms-sites-list-table
		$s = strtolower ( stripslashes( trim( $s ) ) );
		// $s = '*'.$s.'*';
		$wild = '%';
		if ( false !== strpos($s, '*') ) {
			$wild = '%';
			$s = trim($s, '*');
		}

		$like_s = esc_sql( like_escape( $s ) );

		// If the network is large and a search is not being performed, show only the latest blogs with no paging in order
		// to avoid expensive count queries.
		if ( !$s && wp_is_large_network() ) {
			if ( !isset($_REQUEST['orderby']) )
				$_GET['orderby'] = $_REQUEST['orderby'] = '';
			if ( !isset($_REQUEST['order']) )
				$_GET['order'] = $_REQUEST['order'] = 'DESC';
		}

		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";

		if ( empty($s) ) {
			return;
			// Nothing to do.
		} elseif ( preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $s ) ||
					preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.?$/', $s ) ||
					preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.?$/', $s ) ||
					preg_match( '/^[0-9]{1,3}\.$/', $s ) ) {
			
			// IPv4 address
			$reg_blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->registration_log} WHERE {$wpdb->registration_log}.IP LIKE ( '{$like_s}$wild' )" );

			if ( !$reg_blog_ids )
				$reg_blog_ids = array( 0 );

			$query = "SELECT *
				FROM {$wpdb->blogs}
				WHERE site_id = '{$wpdb->siteid}'
				AND {$wpdb->blogs}.blog_id IN (" . implode( ', ', $reg_blog_ids ) . ")";
			
		} else {
			
			if ( is_numeric($s) && empty( $wild ) ) {
				$query .= " AND ( {$wpdb->blogs}.blog_id = '{$like_s}' )";
			} elseif ( is_subdomain_install() ) {
				$blog_s = str_replace( '.' . $current_site->domain, '', $like_s );
				$blog_s .= $wild . '.' . $current_site->domain;
				$query .= " AND ( {$wpdb->blogs}.domain LIKE '$blog_s' ) ";
			} else {
				if ( $like_s != trim('/', $current_site->path) )
					$blog_s = $wild . $current_site->path . $like_s . $wild . '/';
				else
					$blog_s = $like_s;
				$query .= " AND  ( {$wpdb->blogs}.path LIKE '$blog_s' )";
			}
		}
		// order by 
		if ( is_subdomain_install() )
			$query .= ' ORDER BY domain ';
		else
			$query .= ' ORDER BY path ';
		
		// limit 
		$pagenum = 1;
		$per_page = 5;
		$query .= " LIMIT " . intval( ( $pagenum - 1 ) * $per_page ) . ", " . intval( $per_page );
		
		// run the query 
		return $wpdb->get_results( $query, ARRAY_A );
	}
	
   /**
	* This displays if the post if coming from somewhere else. 
	*
	*/
	public static function post_display_from() {
		global $post;
		self::$post_from = get_post_meta( $post->ID, '_nsync-from', true);
		
		if( defined( 'NSYNC_PUSH_BASENAME') )
			Nsync_Push::$post_from = self::$post_from;
		
		if( !empty(self::$post_from) ) {
			$bloginfo = get_blog_details( array( 'blog_id' => self::$post_from['blog'] ) ); 
			
			$prefix = ( Nsync::allow_to_publish_to_me( self::$post_from['blog'] )  ? 'Post updated from': 'Originally posted on' ); ?>
			
			<div class="misc-pub-section" id="shell-site-to-post"><?php echo $prefix ?>:
				<em><?php echo $bloginfo->blogname; ?></em> <a href="<?php echo esc_url( $bloginfo->siteurl ) . '/?p='. self::$post_from['post_id']; ?>">view post</a>
			</div>
			<?php
		}
	}
	
   /**
	* Adds a comment under each post in the Posts admin table.  
	*
	* @param  array  $actions    array of action links shown under each post when hovered over. 
	* @param  object  $post      post information.
	*
	* @return array  $actions   list of action links shown under each post when hovered over with the new action added. 
	*/
	public static function posts_display_sync( $actions, $post ) {
		
		self::$post_from = get_post_meta( $post->ID, '_nsync-from', true );
		
		if( !empty( self::$post_from )  ):
			
			$bloginfo = get_blog_details( array( 'blog_id' => self::$post_from['blog'] ) );
			
			$end = '<em>'.$bloginfo->blogname.'</em> <a href="'.esc_url( $bloginfo->siteurl ).'/?p='.self::$post_from['post_id'].'"> | View Post</a>';
			
			$prefix = ( Nsync::allow_to_publish_to_me( self::$post_from['blog'] )  ? 'Post updated from': 'Originally posted on' );
			
			$actions['sync'] = "<p>" . $prefix . ": " . $end . "</p>";
			
		endif;
		
		return $actions;
	}
	
   /**
	* Sets which sites are allowed to push content to site. 
	*
	* @param  array  $blog_id   id of blog.
	*
	* @return boolean   if the site can publish or not. 
	*/
	public static function allow_to_publish_to_me( $blog_id ) {
	
		if( !empty( self::$settings) ):
			
			if( is_array(self::$settings['active']) && in_array($blog_id, self::$settings['active'] ) )
				return true;
		endif;
		
		return false;
	}
	
}