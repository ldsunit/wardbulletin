<div class="wrap">
	<h1>WP Scripts & Styles Optimizer</h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo admin_url( 'admin.php?page=wpso_global' ); ?>" class="nav-tab"><?php _e( 'Global', 'wp-script-optimizer' ); ?></a>
		<a href="<?php echo admin_url( 'admin.php?page=wpso_single' ); ?>" class="nav-tab nav-tab-active"><?php _e( "Single pages", 'wp-script-optimizer' ); ?></a>
	</h2>

	<div class="wpso-flex">
		<p><?php _e( "Here you can add any of your frontend-pages to show all scripts & stylesheets that are enqueued on that specific page. Then you can customize these files and save your settings, so you can change them again later if needed. The view is divided in scripts on the left and stylesheets on the right side. You can switch between header and footer on both lists. Keep in mind that single page settings always overwrite global settings. Click on help in the upper right corner for more infos.", 'wp-script-optimizer' ); ?></p>
	</div>

	<?php if ( Wpso::$query_string != 'global' ) : ?>
		<hr>
		<p style="text-align:center;">
			<button type="button" class="wpso-update-list button button-large" data-querystring="<?php echo Wpso::$query_string; ?>"><i class="fa fa-refresh"></i>&nbsp;&nbsp;<strong><?php _e( 'Update this page', 'wp-script-optimizer' ); ?></strong></button>
			<button type="button" class="wpso-delete-list button button-large" data-querystring="<?php echo Wpso::$query_string; ?>"><i class="fa fa-trash"></i>&nbsp;&nbsp;<strong><?php _e( 'Delete this page', 'wp-script-optimizer' ); ?></strong></button>
			<button type="button" class="wpso-sync-list button button-large" data-querystring="<?php echo Wpso::$query_string; ?>"><i class="fa fa-cloud-download"></i>&nbsp;&nbsp;<strong><?php _e( 'Sync page with global', 'wp-script-optimizer' ); ?></strong></button>
		</p>
		<hr>
	<?php endif; ?>

	<div id="poststuff">
		<div class="wpso-grid">
			<div style="width:24%;">
				<div class="postbox">
					<h2><i class="fa fa-sitemap"></i>&nbsp;&nbsp;<span><?php _e( 'Add page', 'wp-script-optimizer' ); ?></span></h2>
					<div class="inside">
						<p class="description" style="padding-bottom:10px;"><?php _e( "Get Scripts & Styles for a specific url", 'wp-script-optimizer' ); ?></p>
						<div class="main">
							<div class="wpso-overlay-wrapper">
								<input type="text" id="wpso-search-page-input" placeholder="<?php _e( 'Paste or type a url here...', 'wp-script-optimizer' ); ?>" autofocus>
								<span class="wpso-overlay" style="right:10px;"></span>
							</div>
							<div id="wpso-search-page-result"></div>
						</div>
					</div>
				</div>
				<div class="postbox">
					<h2><i class="fa fa-files-o"></i>&nbsp;&nbsp;<span><?php _e( "Saved pages", 'wp-script-optimizer' ); ?></span></h2>
					<div class="inside">
						<p class="description" style="padding-bottom:10px;"><?php _e( "This list shows all saved query strings (pages)", 'wp-script-optimizer' ); ?></p>
						<p class="wpso-saved-urls-actions"><a href="" id="wpso-update-all-lists"><?php _e( "Update all", 'wp-script-optimizer' ); ?></a> | <a href="" class="delete" id="wpso-delete-all-lists"><?php _e( "Delete all", 'wp-script-optimizer' ); ?></a></p>
						<div class="main">
							<div id="wpso-saved-urls-list"></div>
						</div>
					</div>
				</div>
			</div>

			<?php
			if ( Wpso::$query_string != 'global' ) : ?>
				<div style="width:38%;">
					<div class="postbox">
						<h2><i class="fa fa-code"></i>&nbsp;&nbsp;<span><?php echo sprintf( __( 'Scripts (%d)', 'wp-script-optimizer' ), Wpso::get_count( 'script', Wpso::$query_string ) ) ?></span></h2>
						<div class="inside">
							<div class="main">

								<?php
								switch ( $_COOKIE['wpso_tab_sc_' . base64_encode( Wpso::$query_string )] ) {
									case 'header':
										$active_scripts_tab_header 		   = ' nav-tab-active';
										$hidden_scripts_tab_content_footer = ' hidden';
										break;

									case 'footer':
										$active_scripts_tab_footer 		   = ' nav-tab-active';
										$hidden_scripts_tab_content_header = ' hidden';
										break;

									default:
										$active_scripts_tab_header 		   = ' nav-tab-active';
										$hidden_scripts_tab_content_footer = ' hidden';
										break;
								}

								$scripts_header = new WpsoList( 'script', 0 );
								$scripts_footer = new WpsoList( 'script', 1 ); ?>

								<h3 class="nav-tab-wrapper" id="wpso-tabs-scripts">
									<a href="" class="nav-tab<?php echo $active_scripts_tab_header ?>" data-tab="header">
										<i class="fa fa-arrow-up"></i>&nbsp;&nbsp;<?php _e( 'Header', 'wp-script-optimizer' ); ?> (<?php echo $scripts_header->found_items; ?>)
									</a>
									<a href="" class="nav-tab<?php echo $active_scripts_tab_footer ?>" data-tab="footer">
										<i class="fa fa-arrow-down"></i>&nbsp;&nbsp;<?php _e( 'Footer', 'wp-script-optimizer' ); ?> (<?php echo $scripts_footer->found_items; ?>)
									</a>
								</h3>

								<div class="wpso-tabs-wrapper" id="wpso-tabs-content-scripts">
									<div class="wpso-tab<?php echo $hidden_scripts_tab_content_header ?>" data-tab="header">
										<form method="post">
											<?php
											$scripts_header->display(); ?>
										</form>
									</div>
									<div class="wpso-tab<?php echo $hidden_scripts_tab_content_footer ?>" data-tab="footer">
										<form method="post">
											<?php
											$scripts_footer->display();	?>
										</form>
									</div>
								</div>

							</div>
						</div>
					</div>
				</div>
				<div style="width:38%;">
					<div class="postbox">
						<h2><i class="fa fa-css3"></i>&nbsp;&nbsp;<span><?php echo sprintf( __( 'Styles (%d)', 'wp-script-optimizer' ), Wpso::get_count( 'style', Wpso::$query_string ) ); ?></span></h2>
						<div class="inside">
							<div class="main">

								<?php
								switch ( $_COOKIE['wpso_tab_st_' . base64_encode( Wpso::$query_string )] ) {
									case 'header':
										$active_styles_tab_header 		  = ' nav-tab-active';
										$hidden_styles_tab_content_footer = ' hidden';
										break;

									case 'footer':
										$active_styles_tab_footer 		  = ' nav-tab-active';
										$hidden_styles_tab_content_header = ' hidden';
										break;

									default:
										$active_styles_tab_header 		  = ' nav-tab-active';
										$hidden_styles_tab_content_footer = ' hidden';
										break;
								}

								$styles_header = new WpsoList( 'style', 0 );
								$styles_footer = new WpsoList( 'style', 1 ); ?>

								<h3 class="nav-tab-wrapper" id="wpso-tabs-styles">
									<a href="" class="nav-tab<?php echo $active_styles_tab_header ?>" data-tab="header">
										<i class="fa fa-arrow-up"></i>&nbsp;&nbsp;<?php _e( 'Header', 'wp-script-optimizer' ); ?> (<?php echo $styles_header->found_items; ?>)
									</a>
									<a href="" class="nav-tab<?php echo $active_styles_tab_footer ?>" data-tab="footer">
										<i class="fa fa-arrow-down"></i>&nbsp;&nbsp;<?php _e( 'Footer', 'wp-script-optimizer' ); ?> (<?php echo $styles_footer->found_items; ?>)
									</a>
								</h3>

								<div class="wpso-tabs-wrapper" id="wpso-tabs-content-styles">
									<div class="wpso-tab<?php echo $hidden_styles_tab_content_header ?>" data-tab="header">
										<form method="post">
											<?php
											$styles_header->display(); ?>
										</form>
									</div>
									<div class="wpso-tab<?php echo $hidden_styles_tab_content_footer ?>" data-tab="footer">
										<form method="post">
											<?php
											$styles_footer->display();	?>
										</form>
									</div>
								</div>

							</div>
						</div>
					</div>
				</div>
			<?php
			else : ?>
				<div style="width:76%;">
					<div class="postbox wpso-action-text">
						<div class="inside">
							<div class="main">
								<?php _e( "Add a new page <br> -- or -- <br> choose a previously saved page", 'wp-script-optimizer' ); ?>
							</div>
						</div>
					</div>
				</div>
			<?php
			endif; ?>
		</div>
	</div>

	<?php $this->admin_notices(); ?>

</div>
