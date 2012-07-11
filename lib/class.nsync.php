<?php 


class Nsync {
	// 
	static $settings = array();
	static $currently_publishing = false;
	public static function init() {
		
		
	}
	
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
		wp_register_script( 'nsync-post-to', NSYNC_DIR_URL."/js/post-to.js", array( 'jquery', 'hoverIntent' ), '1.0', true );
		wp_register_style( 'nsync-post-to', NSYNC_DIR_URL."/css/post-to.css", null, '1.0', 'screen' );
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
			$args = array( 'who' => 'authors' );
		unset($blog);
		$all_blogs = null;
		
		$users = get_users( $args );

		
		// limit 
		(int)$pagenum = ( isset( $_GET['num'] ) ? $_GET['num'] : 1 );
		$per_page = 10;

		$users = array_slice( $users, intval( ( $pagenum - 1 ) * $per_page ), intval( $per_page ) );
		// todo: this might exlode if there are many users. like on a site that has all the 10 authors
		foreach( $users as $user ):
			
			if( $current_user->id != $user->ID ): // don't add the current user blogs they are listed above anyways
				
				$user_blogs = get_blogs_of_user( $user->ID );
				if( is_array( $user_blogs ) ):
					
					foreach( $user_blogs as $blog ):
						if( !in_array( $blog->userblog_id, $showen ) && ( $current_blog_id != $blog->userblog_id ) )
						$all_blogs[ $blog->userblog_id ] = $blog; // make the all_blogs unique
					endforeach;
				endif;
			endif;
		endforeach;
		if( is_array( $all_blogs ) ):?>
		<p style="margin:20px 0 0;">Select a site from the current site authors.</p>
		<?php
		foreach( $all_blogs as $blog ): ?>
			<label> 
				<input name="nsync_options[active][]" type="checkbox" value="<?php echo esc_attr( $blog->userblog_id ); ?>" /> 
				<?php echo $blog->blogname;?> <small><?php echo $blog->siteurl;?></small>
			</label><br />
			<?php 
		endforeach;
		endif;
		
		
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
	
	public static function post_to_script_n_style() {
		
		wp_enqueue_script( 'nsync-post-to' );
		wp_enqueue_style( 'nsync-post-to' );
		
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
		$from = get_post_meta( $post->ID, 'nsync-from', false );
		
		if( !empty($from) ) {
		
			var_dump("from", $from );
		
		}
	}
	/* POST SIDE */
	public static function user_select_site() {
		global $post;
		$post_to = get_option( 'nsync_post_to' );
		
		// change this line if you also want to effect pages. 
		if( is_array( $post_to ) && $post->post_type == 'post' && !empty($post_to) ):
		
			$previous_to = get_post_meta( $post->ID, 'nsync-to', false );
			
			foreach( $post_to as $blog_id ): 
					
					$blog = get_blog_details( array( 'blog_id' => $blog_id ) );
					$blogs[] = $blog;
					
					if( isset( $previous_to[0][$blog_id] ) )
						$site_diplay[] = $blog->blogname;
						
			endforeach;
			
			?>
			<div class="misc-pub-section" id="shell-site-to-post">
				
				<label >Also publish to:</label>
				
				<span id="site-display"> <strong><?php echo ( is_array( $site_diplay ) ? implode( $site_diplay, ","): ""); ?></strong></span>
				
				<div id="site-to-post" class="hide-if-js">
					<?php foreach( $blogs as $blog ): ?>
					<label><input type="checkbox" name="nsync_post_to[]" value="<?php echo esc_attr($blog->blog_id); ?>" <?php echo checked( (bool)$previous_to[0][ $blog->blog_id] ); ?> alt="<?php echo esc_attr( $blog->blogname);?>" /> <?php echo $blog->blogname;?> <small><?php echo $blog->siteurl;?></small></label>
					<?php endforeach; ?>
				</div>
			</div>
			<?php 
		    wp_nonce_field( 'nsync' , 'nsync_noncename' , false );
		endif;
	}

}























