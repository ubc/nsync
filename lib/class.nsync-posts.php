<?php

class Nsync_Posts {
	static $currently_publishing = false;
	static $current_blog_id = null;
	static $own_post = null;
	static $remote_post = null;
	static $previous_to = null;
	static $new_categories = array();
	static $attachments = null;
	static $current_upload = array();
	static $update_post = false;
	static $replacement = array();
	static $current_attach_data = array();
	
	
	public static function save_postdata( $post_id, $post ) {
	
		// verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		  return;
		
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['nsync_noncename'], 'nsync' ) )
			return;
	
		// Check permissions
		if ( 'post' == $_POST['post_type'] && $post->post_type == 'post'  ){
			if ( !current_user_can( 'edit_post', $post_id ) )
			    return;
		} else {
			return;
		}
		
		// don't go into an infinate loop
		if( self::$currently_publishing )
			return;
		
		self::$current_blog_id = get_current_blog_id();
		
		self::$currently_publishing = true;
		
		// OK, we're authenticated: we need to find and save the data
		$blogs_to_post_to = $_POST['nsync_post_to'];
		
		// where did we previously created or updated a post… used for making sure that we update the same post
		self::$previous_to = get_post_meta( $post_id, 'nsync-to', false );
				
		// we are going to remove stuff from here
		Nsync_Posts::clean_post( $post ); // 
		Nsync_Posts::setup_taxonomies( $post_id );
		Nsync_Posts::setup_attachments( $post_id );
     	
		
		if( is_array( $blogs_to_post_to ) ):
			
			$from = array( 'blog' => self::$current_blog_id, 'post_id' => $post_id );
			$to = array(); // a list of sites and post that we are publishing to
			
			foreach( $blogs_to_post_to as $blog_id ):
				
				switch_to_blog( $blog_id ); // save the post on the a different site
				
				unset( $nsync_options );
				$nsync_options = get_option( 'nsync_options' );
				// can I accually post there? 
				
				// double check that the current site accually allows you to publish here
				if( in_array( self::$current_blog_id, $nsync_options['active'] ) ):
					// create the new post
					
					Nsync_Posts::set_post_id( $blog_id );
					Nsync_Posts::replicate_categories( $nsync_options );
					Nsync_Posts::set_post_status( $nsync_options, $post );
					
					$new_post_id = Nsync_Posts::insert_post( $nsync_options );			
					
					Nsync_Posts::replicate_attachments( $nsync_options, $new_post_id );
					
					
					if( isset( $new_post_id ) ):
						$to[ $blog_id ] = $new_post_id;
						// update the post 
						// update the to
						add_post_meta( $new_post_id, 'nsync-from', $from, true );
					endif;
					
				endif;
				
				restore_current_blog(); // revet to the current blog 
			endforeach;
			
			Nsync_Posts::update_nsync_to( $post_id, $to );
			
		
		endif;
		
		// Do something with $mydata 
		// probably using add_post_meta(), update_post_meta(), or 
		// a custom table (see Further Reading section below)
	}
	
	public static function clean_post( $post ) {
		
		// remove unwanted things
		unset(
			$post->ID,
			$post->comment_status, 
			$post->ping_status,
			$post->to_ping,
			$post->pinged,
			$post->guid,
			$post->filter,
			$post->ancestors
		);
		
		self::$remote_post = $post;
	}
	public static function clean_attachment( $attachment ) {	
		unset(
			$attachment->ID,
			$attachment->comment_status, 
			$attachment->ping_status,
			$attachment->to_ping,
			$attachment->pinged,
			$attachment->guid,
			$attachment->filter,
			$attachment->post_type
		);
		return $attachment;
	}
	
	public static function path_to_file( $attachment_guid, $upload ) {
		
		$filename = explode( $upload["subdir"], $attachment_guid  );
		return $upload["path"].$filename[1];
	}
	
	public static function copy_file( $attachment_guid ) {
		
		$current_file 	= Nsync_Posts::path_to_file( $attachment_guid, self::$current_upload );
		$new_file 		= Nsync_Posts::path_to_file( $attachment_guid, wp_upload_dir() );
	
		// copy the file
		if( copy( $current_file, $new_file ) )
			return $new_file;
		else
			return false;
	}
	public static function setup_taxonomies( $post_id ) {
	
		$taxonomies = array('category', 'post_tag');
		
		$terms = wp_get_object_terms( $post_id, $taxonomies );
		$new_terms = array();
		
		
		foreach( $terms as $term ):
			if($term->taxonomy == 'category'):
				self::$new_categories[] = $term->name; // categories neews to have 
			else:
				$new_terms[$term->taxonomy][] = $term->name;
			endif;
		endforeach;
		
		self::$remote_post->tax_input = $new_terms;
	}
	
	public static function setup_attachments( $post_id ) {
		$args = array(
			'numberposts' => -1,
			'order'=> 'DESC',
			'post_parent' => $post_id,
			'post_type' => 'attachment'
		);

	self::$attachments = Nsync_Posts::get_attachments( $post_id );  //returns Array ( [$image_ID]... 
	self::$current_upload = wp_upload_dir();
	
	
	foreach( self::$attachments as $attach ):
		self::$current_attach_data[$attach->ID] = wp_get_attachment_metadata( $attach->ID );
 	endforeach;
	
	}
	
	public static function get_attachments( $post_id ) {
		$args = array(
			'numberposts' => -1,
			'order'=> 'DESC',
			'post_parent' => $post_id,
			'post_type' => 'attachment'
		);

		return get_children( $args );  //returns Array ( [$image_ID]... 
	}
	
	public static function set_post_id( $blog_id ) {
		
		if( isset( self::$previous_to[0][$blog_id] ) ):
			self::$remote_post->ID = (int)self::$previous_to[0][$blog_id]; // we are updating the post 
			self::$update_post = true;
		else:
			unset( self::$remote_post->ID ); // this is going to be a brand new post 
			self::$update_post = false;
		endif;
	}
	
	public static function replicate_categories( $nsync_options ) {
		
		$new_categoriy_ids = array();
					
		foreach( self::$new_categories as $new_category ):
			$new_categoriy_ids[] = wp_create_category( $new_category );
		endforeach;
		
		// add the post to a specific category
		if( isset( $nsync_options['category'] ) 
			&& $nsync_options['category'] != '-1' 
			&& in_array( $nsync_options['category'], $new_categoriy_ids ) ): 
			$new_categoriy_ids[] = $nsync_options['category'];
			
		endif;
		
		if( !empty( $new_categoriy_ids ) ):
			self::$remote_post->post_category = $new_categoriy_ids;
		endif;
	
	}
	
	public static function set_post_status( $nsync_options, $post ) {
		
		// overwrite the post status 
		if( isset( $nsync_options['post_status'] ) && $nsync_options['post_status'] != '0' )
			self::$remote_post->post_status = $nsync_options['post_status'];
		else
			self::$remote_post->post_status = $post->post_status;
	}
	
	public static function insert_post( $nsync_options ) {
		
		if( isset( $nsync_options['force_user'] ) &&  $nsync_options['force_user'] ):
			if( user_can( self::$remote_post->post_author, 'publish_post' ) )
				return wp_insert_post( self::$remote_post );
			else
				return null;
		else:
			return wp_insert_post( self::$remote_post );
		endif;
	}
	
	public static function replicate_attachments( $nsync_options, $new_post_id ) {
		
		
		if( isset( $nsync_options['duplicate_files'] ) &&  $nsync_options['duplicate_files'] ):
			// lets clean up the attacments first though
			if( self::$update_post ){
				$delete_attachements = Nsync_Posts::get_attachments( $post_id );
				if( is_array( $delete_attachements ) ):
					foreach( $delete_attachements as $delete )
						wp_delete_attachment( $delete->ID, true );
				endif;
			}
			foreach( self::$attachments as $attachment):
				
				$current_attachment_id = $attachment->ID;
				// copy over the file 
				$filename = Nsync_Posts::copy_file( $attachment->guid );
				$attachment = Nsync_Posts::clean_attachment( $attachment );
				$attach_id = wp_insert_attachment( $attachment, $filename, $new_post_id );
	 			$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	 			
	  			wp_update_attachment_metadata( $attach_id,  $attach_data );
	  			Nsync_Posts::update_content( $attach_data, self::$current_attach_data[$current_attachment_id], $new_post_id );
			
			endforeach;
		
		endif;
		
	}
	
	public static function update_content( $attach_data, $current_attach_data, $new_post_id ) {
		
		// return;
		$current_url = self::$current_upload['baseurl'];
		$remote_upload =  wp_upload_dir();
		$remote_url = $remote_upload['baseurl'];
		
		// set to empty array so that we don't worry about anything
		self::$replacement = array();
		self::$replacement['current'] = array();
		self::$replacement['remote'] = array();
		
		self::$replacement['current'][]   = $current_url.'/'.$attach_data['file'];
		self::$replacement['remote'][] = $remote_url.'/'.$attach_data['file'];
		
		if( is_array($current_attach_data['sizes']) ):
			foreach( $current_attach_data['sizes'] as $size => $data ):
			
				if( $attach_data['sizes'][$size]['file'] ):
					self::$replacement['current'][]   = $current_url.'/'.$data['file'];
					self::$replacement['remote'][] = $remote_url.'/'.$attach_data['sizes'][$size]['file'];
				endif;
			endforeach;
		endif;
		
		// replace all the string
		$update['ID'] = $new_post_id;
  		$update['post_content'] = str_replace ( self::$replacement['current'] , self::$replacement['remote'] , self::$remote_post->post_content  );
		wp_update_post( $update );
		
	}
	
	public static function update_nsync_to( $post_id, $to ) {
		// update the to			
		if( self::$previous_to == null ) 
			add_post_meta( $post_id, 'nsync-to', $to, true );  
		else
			update_post_meta( $post_id, 'nsync-to', $to, self::$previous_to );
	}
	
}

