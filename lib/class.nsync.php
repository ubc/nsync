<?php 


class Nsync {
	// 
	static $settings = array();
	static $currently_publishing = false;
	static $post_from = array();
	
	public static function admin_init() {
		//
		self::$settings = get_option( 'nsync_options' );
		
		register_setting(
			'writing', // settings page
			'nsync_options', // option name
			array( 'Nsync', 'validate') // validation callback
		);
		
		add_settings_field(
			'nsync_options', // id
			'Network sites publishers', // setting title
			array( 'Nsync', 'add_network_sites'), // display callback
			'writing', // settings page
			'remote_publishing' // settings section
		);
		
		// register scipts and styles
		wp_register_script( 'nsync-add-site', NSYNC_DIR_URL."/js/add-site.js", array( 'jquery', 'jquery-ui-autocomplete' ), '1.0', true );
		wp_register_style( 'nsync-post-writing', NSYNC_DIR_URL."/css/writing.css", null, '1.0', 'screen' );
	}
	/* SETTINGS */ 
	public static function add_network_sites() {
		global $current_user;
		
		$current_blog_id = get_current_blog_id();
			
		$active = self::$settings['active'];
		?>
		<div id="select-site">
		<?php
		if( is_array($active) ):
			?>
				<p>Selected sites are able to publish content.</p>
				<?php
			foreach( $active as $active_site_id ):
				$details = get_blog_details( array( 'blog_id' => $active_site_id ) );
				
				/*
				$archived = ( $details->archived ? '<span> archived</span> ': '' ); 
				$mature = ( $details->mature ? '<span> mature</span> ': '' );
				$spam	= ( $details->spam ? '<span> spam</span> ': '' );
				<?php echo $archived; ?><?php echo $spam; ?><?php echo $mature; ?>
				*/
				if( ( ! $blog->archived && ! $blog->spam && ! $blog->deleted ) && $active_site_id != $current_blog_id ): ?>
					<label>
						<input name='nsync_options[active][]' type='checkbox' checked="checked" value='<?php echo esc_attr( $active_site_id ); ?>' />
						<?php echo $details->blogname;?> <small><?php echo $details->siteurl;?> </small>
					</label><br />
					<?php
				endif;
			endforeach;
			
		else:
		?><br />
		<p>There are currently no external sites that are able to publish content on your site.</p>
		<?php
		endif;
		?>
		</div>
		<?php 
		
		if( $current_user->id ):
			$user_blogs = get_blogs_of_user( $current_user->id );
			if( is_array( $user_blogs ) ): 
				?>
				<p style="margin:20px 0 0;">Select the from <em>your current sites</em> that want to enabled the external publishing from.</p>
				<?php
				
				foreach( $user_blogs as $blog ): 
					
					if( $current_blog_id != $blog->userblog_id ): ?>
					<label> <input name="nsync_options[active][]" type="checkbox" value="<?php echo esc_attr( $blog->userblog_id ); ?>" /> <?php echo $blog->blogname;?> <small><?php echo $blog->siteurl;?></small></label><br />
					<?php 
					$showen[] = $blog->userblog_id; 
					endif;
				endforeach;
			endif; 
		endif; 
		
		// all the users sites 
		$args = array( 'who' => 'all' );
			unset($blog);
		$all_blogs = null;
		$showen[] = $current_blog_id; 
		$users = get_users( $args );

		
		// limit 
		(int)$pagenum = ( isset( $_GET['num'] ) ? $_GET['num'] : 1 );
		
		$per_page = 10;
		$total_numer_of_users = count($users);
		
		$users = array_slice( $users, intval( ( $pagenum - 1 ) * $per_page ), intval( $per_page ) );
		
		?>
		<p style="margin:20px 0 0;">List of sites by current users</p>
		<?php
		
		// todo: this might exlode if there are many users. like on a site that has all the 10 authors
		foreach( $users as $user ):
			
			if( $current_user->id != $user->ID ): // don't add the current user blogs they are listed above anyways
				if( !$user->deleted ):
				
					$user_blogs = get_blogs_of_user( $user->ID );
					if( is_array( $user_blogs ) ):
							
							
						?>
						<div class="user-blogs">
							<?php echo get_avatar( $user->email, 24 ); ?> <span class="display-name"><?php echo $user->display_name; ?></span>
							
							<div class="user-blogs-list">
							<?php 
							foreach( $user_blogs as $blog ):
								
								if( !in_array( $blog->userblog_id, $showen ) ):
								
									$all_blogs[ $blog->userblog_id ] = $blog; // make the all_blogs unique
								
								
								?>
								<label> 
									<input name="nsync_options[active][]" type="checkbox" value="<?php echo esc_attr( $blog->userblog_id ); ?>" /> 
									<?php echo $blog->blogname;?>
								</label> <a href="<?php echo $blog->siteurl;?>" target="_blank"><small><?php echo $blog->siteurl;?></small></a>
								<br />
								<?php
								endif;
							endforeach;
							?>
							</div>
						</div>
						<?php
					endif; // has any blogs?
					
				endif; // deleted?
			endif;
		endforeach;
		
		
		/*
		if( is_array( $all_blogs ) ):?>
		<p style="margin:20px 0 0;">Select a site from the current site authors.</p>
		
		foreach( $all_blogs as $blog ): ?>
			<label> 
				<input name="nsync_options[active][]" type="checkbox" value="<?php echo esc_attr( $blog->userblog_id ); ?>" /> 
				<?php echo $blog->blogname;?> <small><?php echo $blog->siteurl;?></small>
			</label><br />
			<?php 
		endforeach;
		
		endif;
		*/
		
		?>
		<p>Pages :<?php Nsync::paginate( $total_numer_of_users, $per_page , $pagenum , admin_url( 'options-writing.php' ) ); ?></p>
		<?php
		
		if( current_user_can('manage_sites') ):
			
			?>
			<p style="border:1px solid #EEE; padding:10px; margin-top:10px;">
				<label for="add-site">Search site to add </label><br />
				<input type="text" id="nsync-add-site" class="regular-text" placeholder="url or site name " /> 
				<br />
				<small>Only available to Super Admins</small>
			</p>
			
			
			<p><label>Added Posts will appear in this category by default: <br />
			
			<?php
			wp_dropdown_categories( array(
				"hierarchical"=>1,
				"name"=>"nsync_options[category]",
				"selected"=> self::$settings['category'],
				'show_option_none' => 'None' )
				);
			?>
			</label></p>
			<?php 
			// Let the user check the post
			// todo: add support for the EditFlow plugin
			$post_status = self::$settings['post_status'];
			
			?>
			<p>
			<label>Select the Post Status <br />
			<select name="nsync_options[post_status]">
				<option value="0" 		<?php selected( $post_status, 0 ); ?>     		>Inherit from the post</option>
				<option value="draft" 	<?php selected( $post_status, 'draft' ); ?> 	>Draft</option>
				<option value="publish" <?php selected( $post_status, 'publish' ); ?> 	>Publish</option>
				<option value="pending" <?php selected( $post_status, 'pending' ); ?> 	>Pending</option>
			</select></label> 
			</p>
			<?php 
			// force check to only users that are also members of this blog. 
			
			?>
			<p>
			<label><input type="checkbox" name="nsync_options[duplicate_files]" value="1" <?php checked( self::$settings['duplicate_files'] ); ?> /> Copy files that are attached to the post to this site.</label>
			</p>
			
			<p>
			<label><input type="checkbox" name="nsync_options[force_user]" value="1" <?php checked( self::$settings['force_user'] ); ?> /> Only Authors, Editor and Administrators are able to remotly publish to this site.</label>
			</p>
			<?php
		endif;

	}
	
	public static function paginate( $total, $per_page, $current_page = 1 , $url_start ) {
		
		$total_pages = ceil( $total / $per_page );
		for( $page = 1; $page <= $total_pages ; $page++ ) {
			if($current_page == $page)
				$list_pages[] = $page;	
			else
				$list_pages[] = '<a href="'.$url_start.'?num='.$page.'">'.$page.'</a>';
			
		}
		
		echo implode(" ", $list_pages );
		
	
	}
	
	public static function validate( $input ) {
		
		
		$current_blog_id = get_current_blog_id();
		
		if( is_array( $input['active'] ) )
			$active = array_unique( $input['active'] );
		else
			$active = array();
		
		
		
		if( is_array( self::$settings['active'] ) && !empty( $active ) ):
			$intersect 	= array_intersect( self::$settings['active'], $active );
			
			$to_add 	= array_diff( $active, $intersect );
			
			$to_remove 	= array_diff( self::$settings['active'], $intersect );
			
		elseif( is_array( self::$settings['active'] ) && empty( $active ) ):
			
			$to_remove 	= self::$settings['active'];
		
		else:
			
			$to_add 	= $active;
		endif;
		
		// remove sites that you don't want any more.
		if( !empty( $to_remove ) ):
		
			foreach( $to_remove as $blog_id ):
				
				switch_to_blog( $blog_id );
				
				$post_to = get_option( 'nsync_post_to' );
				
				
				if( is_array($post_to) ):
					// there is nothing to remove if we don't have the $post_to set as an array
					$post_to = array_diff( $post_to , array( $current_blog_id ) );
					update_option( 'nsync_post_to', $post_to );
				
				endif;
				restore_current_blog();
				unset( $post_to ); 
				
			endforeach;
		endif;
		
		// add new sites 
		if( !empty( $to_add ) ):
			foreach( $to_add as $blog_id ):
				if( $blog_id != $current_blog_id ): // never add yourself to the its own blog to the list of blogs 	
					switch_to_blog( $blog_id );
					
					$post_to = get_option( 'nsync_post_to' );
					if( !is_array($post_to) )
						$post_to = array();
					$post_to[] = $current_blog_id;

					update_option( 'nsync_post_to', $post_to );
					
					restore_current_blog();
					unset( $post_to ); 
				endif;
			endforeach;
		endif;
		
		
		$input['active'] = $active;
		
		self::$settings = $input;
		
		return $input;
		
	}
	
	public static function writing_script_n_style() {
		if( current_user_can( 'manage_sites' ) ):
			wp_enqueue_script( 'jquery-ui-autocomplete' ); 
			wp_enqueue_script( 'nsync-add-site' );
			
		endif;
		
		wp_enqueue_style( 'nsync-post-writing' );
	}
	
	public static function ajax_lookup_site() {
		
		if( !current_user_can( 'manage_sites' ) ):
			return "0";
			die();
		endif;
		
		$sites = Nsync::search_site( $_GET['term'] );
			
		foreach( $sites as $site )
			$results[] = array( 'label'=> $site['domain'].$site['path'] , 'value'=> $site['blog_id'] );
			
		echo json_encode( $results );
		die();

	}
		
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
	
	public static function post_from_site() {
		global $post;
		self::$post_from = get_post_meta( $post->ID, 'nsync-from', true);
		
		if( !empty(self::$post_from) ) {
			$bloginfo = get_blog_details( array( 'blog_id' => self::$post_from['blog'] ) );
			?>
			<div class="misc-pub-section" id="shell-site-to-post">This post is currently being updated from <br />
			<a href="<?php echo esc_url( $bloginfo->siteurl );?>"><?php echo $bloginfo->blogname; ?></a>
			</div>
			<?php
		}
	}
}























