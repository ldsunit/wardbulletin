<div class="wrap">
	<h1>WP Scripts & Styles Optimizer</h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo admin_url( 'admin.php?page=wpso_global' ); ?>" class="nav-tab nav-tab-active"><?php _e( 'Global', 'wp-script-optimizer' ); ?></a>
		<a href="<?php echo admin_url( 'admin.php?page=wpso_single' ); ?>" class="nav-tab"><?php _e( "Single pages", 'wp-script-optimizer' ); ?></a>
	</h2>

	<div class="wpso-flex">
		<p><?php _e( "Here you see all scripts & stylesheets that are enqueued on your site's frontpage, so in fact they are mostly included without any conditions (they are global). The view is divided in scripts on the left and stylesheets on the right side. You can switch between header and footer on both lists. You have also several options to customize every single file. Click on help in the upper right corner for more infos.", 'wp-script-optimizer' ); ?></p>
	</div>

	<hr>
	<p style="text-align:center;">
		<button type="button" class="wpso-update-list button button-large" data-querystring="<?php echo Wpso::$query_string; ?>"><i class="fa fa-refresh"></i>&nbsp;&nbsp;<strong><?php _e( 'Get/Update Global Scripts & Styles', 'wp-script-optimizer' ); ?></strong></button>&nbsp;&nbsp;
		<button type="button" class="wpso-delete-list button button-large" data-querystring="<?php echo Wpso::$query_string; ?>"><i class="fa fa-trash"></i>&nbsp;&nbsp;<strong><?php _e( 'Delete global lists', 'wp-script-optimizer' ); ?></strong></button>
	</p>
	<hr>

	<?php
	if ( Wpso::$query_string == 'global' ) : ?>
		<div id="poststuff">
			<div class="wpso-grid wpso-grid-width-1-2">
				<div>
					<div class="postbox">
						<h2><i class="fa fa-code"></i>&nbsp;&nbsp;<span><?php echo sprintf( __( 'Scripts (%d)', 'wp-script-optimizer' ), Wpso::get_count( 'script', Wpso::$query_string ) ); ?></span></h2>
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
				<div>
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
			</div>
		</div>
	<?php
	else : ?>
		<div id="poststuff">
			<div class="postbox">
				<h2><?php _e( 'Wrong Query String', 'wp-script-optimizer' ); ?></h2>
				<div class="inside">
					<div class="main">
						<?php _e( "You have called this site with an other query_string than 'global'. Please use the 'Single pages' tab instead.", 'wp-script-optimizer' ); ?>
					</div>
				</div>
			</div>
		</div>
	<?php
	endif; ?>

	<?php $this->admin_notices(); ?>

</div>
