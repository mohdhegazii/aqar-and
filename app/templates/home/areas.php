<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_featured_areas(){

  ob_start();

  $lang = is_rtl() ? 'ar' : 'en';

  $featured_areas = carbon_get_theme_option( 'jawda_home_featured_areas_'.$lang );

  if( isset($featured_areas) && !empty($featured_areas) && $featured_areas !== false ):

  ?>

  <!--Featured Area-->
	<div class="featured-area">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="headline">
						<div class="main-title"><?php get_text('المدن الاكثر شهرة','Most popular cities'); ?></div>
					</div>
				</div>
			</div>
			<div class="row">

        <?php

    	  foreach ($featured_areas as $area) {
          $city_id = $area['jawda_home_area_'.$lang];
          $term = get_term( $city_id );
          $img_id = carbon_get_term_meta( $city_id, 'jawda_thumb' );
          $image = wp_get_attachment_url($img_id,'medium');
          $cityname = $term->name;
          $citylink = get_term_link($term);
          $projectscount = $term->count;

        ?>

				<div class="col-md-3">
					<div class="area-box">
						<a href="<?php echo $citylink; ?>" class="area-img">
							<img loading="lazy" src="<?php echo $image; ?>" width="500" height="300" alt="<?php echo $cityname; ?>" />
						</a>
						<div class="area-data">
							<span class="area-title"><a href="<?php echo $citylink; ?>"><?php echo $cityname; ?></a> </span>
							<span class="project-no"><?php echo $projectscount; ?> <?php get_text('وحدة','unit'); ?></span>
							<a href="<?php echo $citylink; ?>" class="area-btn" aria-label="details"><i class="icon-left-big"></i></a>
						</div>
						<a href="<?php echo $citylink; ?>" class="area-link"></a>
					</div>
				</div>

      <?php } ?>

			</div>
		</div>
	</div>
	<!--End Featured Area-->

  <?php

  endif;

  $content = ob_get_clean();
  echo minify_html($content);


}
