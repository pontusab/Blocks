<?php
	
/**
 * Add blocks Settings page
 * @since 0.1
 * @return the settings menu
 */

function blocks_settings_page_init() {
	$settings_page = add_submenu_page( 'edit.php?post_type=blocks', __('Settings', 'blocks'), __('Settings', 'blocks'), 'manage_options', 'blocks-settings', 'blocks_settings_page' );
	add_action( "load-{$settings_page}", 'blocks_load_settings_page' );
}
add_action( 'admin_menu', 'blocks_settings_page_init' );


/**
 * Load and save the 
 * Settings page
 * @since 0.1
 */

function blocks_load_settings_page() {
	if ( isset( $_POST["blocks_save"] ) && $_POST["blocks_save"] == 'save' ) {
		check_admin_referer( "blocks-settings-page" );
		blocks_save_plugin_options();
		$url_parameters = isset( $_GET['tab'] ) ? 'updated=true&tab=' . $_GET['tab'] : 'updated=true';
		wp_redirect( admin_url( 'edit.php?post_type=blocks&page=blocks-settings&' . $url_parameters ) );
		exit;
	}
}


/**
 * Save the settings 
 * multi-array
 * @since 0.1
 */

function blocks_save_plugin_options() {
	$settings = get_option( 'blocks' );

	$tmp_area = isset( $_POST['areas'] ) ? $_POST['areas'] : null;
	$exclude  = isset( $_POST['exclude'] ) ? $_POST['exclude'] : null;

	$areas = array();

	if( ! empty( $tmp_area ) ) { 

		for ( $i = count( $tmp_area['area'] ) - 1; $i >= 0; $i-- ) {
			$area  = $tmp_area['area'][$i];
			$name  = $tmp_area['name'][$i];
			$desc  = $tmp_area['desc'][$i];

			if( !array_key_exists( $area, $areas ) && blocks_check_area_values( $area, $name, $desc ) ) {
				$areas[$area] = array(
					'name' => $name,
					'desc' => $desc
				);
			}
		}
	}

	if ( $_GET['page'] == 'blocks-settings' ) 
	{ 
		$settings['areas'] 	 = $areas;
		$settings['exclude'] = $exclude;
	}
	update_option( 'blocks', $settings );
}


/**
 * The options for 
 * The registed tabs
 * @since 0.1
 * @return Settings page
 */

function blocks_settings_page() {
	$settings = get_option( 'blocks' );
	?>	
	<div class="wrap">		
		<?php
			if( isset( $_GET['updated'] ) && 'true' == esc_attr( $_GET['updated'] ) ) {
				echo '<div class="updated" ><p>' . __('Block settings updated.','blocks') . '</p></div>';
			}
		?>

		<div id="poststuff">
			<form method="post" action="<?php network_admin_url( 'edit.php?post_type=blocks&page=blocks-settings' ); ?>">
				<?php
				wp_nonce_field( "blocks-settings-page" ); 
				
				if ( $_GET['page'] == 'blocks-settings' ) { 

					if( isset( $settings['areas'] ) ) 
					{
						$areas = $settings['areas'];
					} else {
						$areas = array();
					}
					?>

					<table class="form-table">
						<tbody class="add-area">

							<tr>
								<th scope="row">
									<h3><?php _e('Add new areas', 'blocks'); ?>.</h3>
									<p>
										<?php _e('Here you can edit or add new areas', 'blocks'); ?>.
										<?php _e('Add a key, Name and Description', 'blocks'); ?>.
										<?php _e('The Name and Description will be visable in WordPress-admin', 'blocks'); ?>.
									</p>
								</th>

								<td>
									<div class="wp-box">
										<table class="widefat">
											<thead>
												<tr>
													<th><?php _e('Key', 'blocks'); ?></th>
													<th><?php _e('Name', 'blocks'); ?></th>
													<th><?php _e('Description', 'blocks'); ?></th>
												</tr>
											</thead>
											<tbody>
												<tr class="blocks-area-row">
													<td><input type="text" name="areas[area][]" placeholder="<?php _e('Key','blocks'); ?>" /></td>
													<td><input type="text" name="areas[name][]" placeholder="<?php _e('Name','blocks'); ?>" /></td>
													<td><input type="text" name="areas[desc][]" placeholder="<?php _e('Description','blocks'); ?>" /></td>
												</tr>
												<?php foreach ($areas as $areaKey => $area) : ?>
													<tr class="blocks-area-row">
														<td><input type="text" name="areas[area][]" value="<?php echo $areaKey; ?>" /></td>
														<td><input type="text" name="areas[name][]" value="<?php echo $area['name']; ?>" /></td>
														<td>
															<input type="text" name="areas[desc][]" value="<?php echo $area['desc']; ?>" />
															<a class="button blocks-remove-area"><?php _e('Remove', 'blocks'); ?></a>
														</td>
													</tr>
												<?php endforeach; ?>
												
											</tbody>
										</table>
									</div>
								</td>

							</tr>

							<tr>

								<th scope="row">
									<h3><?php _e( 'Exclude blocks', 'blocks' ); ?>.</h3>
									<p>
										<?php _e( 'Here you can exclude blocks on specific post types', 'blocks' ); ?>.
									</p>
								</th>

								<td>
									<fieldset>
										<?php 
											$post_types = blocks_post_types();
												
												$output = '';

												foreach( $post_types as $type ) 
												{
													$object = get_post_type_object( $type );

													$output .= '<label for="'. $type .'">';
														$output .= '<input name="exclude['. $type .']" type="checkbox" id="'. $type .'" '. checked( isset($settings['exclude'][$type]) ? $settings['exclude'][$type] : '', $type, false ).' value="'. $type .'"> ';
														$output .= $object->labels->singular_name . '<br />';
													$output .= '</label>';
												}

											echo $output;
										?>
									</fieldset>

								</td>
							</tr>
							
						</tbody>
					</table>
					<?php
				}
				?>

				<p class="submit">
					<input type="submit" name="Submit"  class="button-primary button-large button" value="<?php _e('Save Settings', 'blocks'); ?>" />
					<input type="hidden" name="blocks_save" value="save" />
				</p>
			</form>	
		</div>
	</div>
<?php
}
