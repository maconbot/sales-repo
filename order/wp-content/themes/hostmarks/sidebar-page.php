<?php
/**
 * The Sidebar containing the main widget areas.
 */
?>
		<div id="sidebar" class="widget-area col300" role="complementary">

			<?php if ( get_theme_mod( 'hostmarks_banner_sidebar' ) ) : ?>
            <div id="banner-sidebar" >
                <?php echo wp_kses_post( get_theme_mod( 'hostmarks_banner_sidebar' ) ); ?>
            </div>
            <?php endif; ?>
			
			<?php if ( ! dynamic_sidebar( 'sidebar-page' ) ) : ?>

				

			<?php endif; // end sidebar widget area ?>
		</div><!-- #sidebar .widget-area -->
