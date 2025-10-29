<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_main
----------------------------------------------------------------------------- */

function get_my_home_slider(){

  ob_start();

  $langn = is_rtl() ? 'ar' : 'en';

  $home_slider = carbon_get_theme_option( 'jawda_home_slider_'.$langn );

  if( isset($home_slider) && !empty($home_slider) && $home_slider !== false ):

  ?>

  <!--Banner-->
	<div class="home-banner">
		<div class="container-fluid">
			<div class="row no-padding">
				<div id="banner-slider" class="col-md-12">

          <?php

            foreach ($home_slider as $slide) {
              $project_id = $slide['jawda_home_slider_post_'.$langn];
              $slideimage = get_the_post_thumbnail_url($project_id,'large');
              $slidetitle = get_the_title($project_id);
              $slidelink = get_the_permalink($project_id);
          ?>

					<div class="slide">
						<div class="cover"></div>
						<img loading="lazy" src="<?php echo $slideimage; ?>" width="1600" height="767" alt="<?php echo $slidetitle; ?>">
						<div class="container">
							<div class="banner-data">
								<span class="data1"><a href="<?php echo $slidelink; ?>"><?php echo $slidetitle; ?></a></span>
								<a href="<?php echo $slidelink; ?>" class="banner-btn"><?php get_text('المزيد من التفاصيل','More details'); ?></a>
							</div>
						</div>
					</div>

          <?php } ?>

				</div>

			</div>
		</div>
	</div>
	<!--End Banner-->
  <?php get_search_box(); ?>
  <?php

  endif;

  $content = ob_get_clean();
  echo minify_html($content);


}






function get_search_box()
{
  ?>
  <!--Hero Search-->
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<div class="hero-search">
					<ul class="tabs">
            <li class="tab-link current" data-tab="tab-1"><?php get_text('المشروعات','Projects'); ?></li>
            <li class="tab-link" data-tab="tab-2"><?php get_text('الوحدات','Properties'); ?></li>
					</ul>

					<div id="tab-1" class="tab-content current">
            <?php jawda_projects_search_box(); ?>
					</div>

					<div id="tab-2" class="tab-content">
            <?php jawda_property_search_box(); ?>
					</div>

				</div>
        <!--
        <div class="advanced"><a href="<?php jawda_home_link(); ?>/?s="><i class="icon-plus"></i> بحث متقدم</a></div>
      -->
    </div>
		</div>
	</div>
	<!--End Search-->
  <?php
}






function jawda_projects_search_box()
{
  ?>
  <form method="GET" action="<?php echo home_url( '/' ); ?>">
    <input type="hidden" name="st" value="1">
    <div class="wpas-field">
      <input name="s" placeholder="<?php get_text('اسم المشروع','project name'); ?>" class="search-input search-autocomplete-projects" aria-label="project-name">
    </div>
    <div class="wpas-field">
      <?php $projects_area = get_terms( array( 'taxonomy' => 'projects_area','hide_empty' => true,'parent' => 0) ); ?>
      <select name="city" class="wpas-select search-select">
        <option selected disabled><?php get_text('الموقع','City'); ?></option>
        <?php if ( is_array($projects_area) AND !empty($projects_area) ): ?>
          <?php foreach ($projects_area as $area): ?>
            <option value="<?php echo $area->term_id; ?>"><?php echo $area->name; ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>
    <div class="wpas-field">
      <?php $projects_type = get_terms( array( 'taxonomy' => 'projects_type','hide_empty' => true,'parent' => 0) ); ?>
      <select name="type" class="wpas-select search-select">
        <option selected disabled><?php get_text('نوع المشروع','Type'); ?></option>
        <?php if ( is_array($projects_type) AND !empty($projects_type) ): ?>
          <?php foreach ($projects_type as $type): ?>
            <option value="<?php echo $type->term_id; ?>"><?php echo $type->name; ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>
    <div class="wpas-submit-field wpas-field">
      <input type="submit" class="search-submit" value="<?php get_text('بحث','Search'); ?>">
    </div>
  </form>
  <?php
}


function jawda_property_search_box()
{
  ?>
  <form method="GET" action="<?php echo home_url( '/' ); ?>">
    <input type="hidden" name="st" value="2">
    <div class="wpas-field">
      <input name="s" placeholder="<?php get_text('ابحث عن','Search for'); ?>" class="search-input search-autocomplete-properties" aria-label="project-name">
    </div>
    <div class="wpas-field">
      <?php $projects_area = get_terms( array( 'taxonomy' => 'property_city','hide_empty' => true,'parent' => 0) ); ?>
      <select name="city" class="wpas-select search-select">
        <option selected disabled><?php get_text('الموقع','City'); ?></option>
        <?php if ( is_array($projects_area) AND !empty($projects_area) ): ?>
          <?php foreach ($projects_area as $area): ?>
            <option value="<?php echo $area->term_id; ?>"><?php echo $area->name; ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>
    <div class="wpas-field">
      <?php $projects_type = get_terms( array( 'taxonomy' => 'property_type','hide_empty' => true,'parent' => 0) ); ?>
      <select name="type" class="wpas-select search-select">
        <option selected disabled><?php get_text('نوع الوحدة','Type'); ?></option>
        <?php if ( is_array($projects_type) AND !empty($projects_type) ): ?>
          <?php foreach ($projects_type as $type): ?>
            <option value="<?php echo $type->term_id; ?>"><?php echo $type->name; ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>
    <div class="wpas-submit-field wpas-field">
      <input type="submit" class="search-submit" value="<?php get_text('بحث','Search'); ?>">
    </div>
  </form>
  <?php
}
