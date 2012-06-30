<?php 


class Nsync {

	
	public static function init() {
		
		
	}
	
	public static function admin_init() {
		register_setting(
			'writing', // settings page
			'nsync_options', // option name
			array( 'Nsync', 'display_input') // validation callback
		);
			
		add_settings_field(
			'nsync_options', // id
			'Post to', // setting title
			array( 'Nsync', 'display_input'), // display callback
			'writing', // settings page
			'remote_publishing' // settings section
		);
	
	}
	
	public static function display_input() {
			$options = get_option( 'nsync_options' );
			$value = $options['boss_email'];
			// echo the field
			
			?>
			
			<input name='nsync_options[allowed][]' type='checkbox' value='<?php echo esc_attr( $value ); ?>' /> Boss wants to get a mail when a post is published
			<?php
	
	}
	
	public static function validate() {
	
	}
	
}