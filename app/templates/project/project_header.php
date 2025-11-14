<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# project_header
----------------------------------------------------------------------------- */

function get_my_project_header(){

  ob_start();

  $post_id = get_the_ID();
  $price = carbon_get_post_meta( $post_id, 'jawda_price' );
  $installment = carbon_get_post_meta( $post_id, 'jawda_installment' );
  $down_payment = carbon_get_post_meta( $post_id, 'jawda_down_payment' );
  $size = carbon_get_post_meta( $post_id, 'jawda_size' );
  $year = carbon_get_post_meta( $post_id, 'jawda_year' );
  $attachments = carbon_get_post_meta( $post_id, 'jawda_attachments' );
  $faqs = carbon_get_post_meta( $post_id, 'jawda_faq' );

  // Developer
  $developer = get_the_terms( get_the_ID(), 'projects_developer' );
  $dev_name = $dev_link = NULL;
  if( isset($developer[0]->term_id) )
  {
    $dev_name = $developer[0]->name;
    $dev_link = get_term_link($developer[0]);
  }

  // Location Breadcrumbs
  global $wpdb;
  $lang = is_rtl() ? 'ar' : 'en';
  $name_col = is_rtl() ? 'name_ar' : 'name_en';
  $breadcrumbs_locations = [];

  $gov_id = get_post_meta($post_id, 'loc_governorate_id', true);
  $city_id = get_post_meta($post_id, 'loc_city_id', true);
  $district_id = get_post_meta($post_id, 'loc_district_id', true);

  if ($gov_id) {
      $query = $wpdb->prepare("SELECT id, {$name_col} as name FROM {$wpdb->prefix}locations_governorates WHERE id = %d", $gov_id);
      $gov = $wpdb->get_row($query);
      if ($gov) {
          $breadcrumbs_locations[] = ['type' => 'governorate', 'id' => $gov->id, 'name' => $gov->name];
      }
  }
  if ($city_id) {
      $query = $wpdb->prepare("SELECT id, {$name_col} as name FROM {$wpdb->prefix}locations_cities WHERE id = %d", $city_id);
      $city = $wpdb->get_row($query);
      if ($city) {
          $breadcrumbs_locations[] = ['type' => 'city', 'id' => $city->id, 'name' => $city->name];
      }
  }
  if ($district_id) {
      $query = $wpdb->prepare("SELECT id, {$name_col} as name FROM {$wpdb->prefix}locations_districts WHERE id = %d", $district_id);
      $district = $wpdb->get_row($query);
      if ($district) {
          $breadcrumbs_locations[] = ['type' => 'district', 'id' => $district->id, 'name' => $district->name];
      }
  }

  // Prepare breadcrumbs for display
  $temp_breadcrumbs = [];
  foreach ($breadcrumbs_locations as $loc) {
      $catalog_id = find_catalog_by_location($loc['type'], $loc['id'], $lang);
      if ($catalog_id) {
          $temp_breadcrumbs[] = [
              'short_name' => $loc['name'],
              'full_title' => get_the_title($catalog_id),
              'link' => get_permalink($catalog_id)
          ];
      }
  }
  $breadcrumbs_locations = $temp_breadcrumbs;

  $master_catalog_id = find_master_catalog($lang);
  if ($master_catalog_id) {
      $master_catalog_title = get_the_title($master_catalog_id);
      $master_catalog_short_name = is_rtl() ? 'مصر' : 'Egypt';
      $master_catalog_link = get_permalink($master_catalog_id);
  } else {
      $master_catalog_title = is_rtl() ? 'المشروعات الجديدة' : 'New Projects';
      $master_catalog_short_name = is_rtl() ? 'مصر' : 'Egypt';
      $master_catalog_link = '#';
  }

  ?>

  <?php get_projects_top_search(); ?>

  <div class="project-hero">
		<div class="container">
			<div class="row no-padding">
				<div class="col-md-5">
					<div class="project-info">

						<!--Breadcrumbs-->
            <div class="breadcrumbs" itemscope="" itemtype="http://schema.org/BreadcrumbList">
              <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item">
                <a class="breadcrumbs__link" href="<?php echo siteurl; ?>" itemprop="item">
                  <span itemprop="name"><?php echo sitename; ?></span>
                </a>
                <meta itemprop="position" content="1">
              </span>
              <span class="breadcrumbs__separator">›</span>
              <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item expandable">
                <a class="breadcrumbs__link" href="<?php echo esc_url($master_catalog_link); ?>" itemprop="item">
                    <span class="short-name"><?php echo esc_html($master_catalog_short_name); ?></span>
                    <span class="full-name"><?php echo esc_html($master_catalog_title); ?></span>
                </a>
                <meta itemprop="position" content="2">
              </span>
              <?php $i = 3; foreach ($breadcrumbs_locations as $loc): ?>
                <span class="breadcrumbs__separator">›</span>
                <span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem" class="breadcrumb-item expandable">
                  <a class="breadcrumbs__link" href="<?php echo esc_url($loc['link']); ?>" itemprop="item">
                      <span class="short-name"><?php echo esc_html($loc['short_name']); ?></span>
                      <span class="full-name"><?php echo esc_html($loc['full_title']); ?></span>
                    </a>
                  <meta itemprop="position" content="<?php echo $i++; ?>">
                </span>
              <?php endforeach; ?>
               <span class="breadcrumbs__separator">›</span>
               <span class="breadcrumb-item-current"><?php echo get_the_title(get_the_ID()); ?></span>
            </div>

						<h1 class="project-headline"><?php echo get_the_title(get_the_ID()); ?></h1>

						<!--Prices-->
            <?php if ( $price !== NULL AND $price != '' ): ?>
              <div class="start-price"> <?php get_text('الأسعار تبدأ من','Prices start from'); ?> <span><?php echo number_format($price); ?></span> <?php get_text('ج.م','EGP'); ?></div>
            <?php endif; ?>

						<!--details-->
						<div class="project-payment">
              <?php if ( $installment !== NULL AND $installment != '' ): ?>
                <div class="payment-details"><?php echo $installment; ?> <?php get_text('سنوات تقسيط','installment years'); ?></div>
              <?php endif; ?>
              <?php if ( $down_payment !== NULL AND $down_payment != '' ): ?>
                <div class="payment-details"><?php echo get_text('المقدم','Down payment'); echo ' '.$down_payment; ?></div>
              <?php endif; ?>
              <?php if ( $year !== NULL AND $year != '' ): ?>
                <div class="payment-details"><?php get_text('التسليم','Delivery'); echo ' '.$year; ?></div>
              <?php endif; ?>
              <?php if ( $size !== NULL AND $size != '' ): ?>
                <div class="payment-details"><?php get_text('مساحات تبدأ من','Spaces starting from'); echo ' '.$size; ?></div>
              <?php endif; ?>
						</div>
						<div class="price-update"><?php get_text('أخر تحديث','Last updated'); echo ' '.jawda_last_updated_date(); ?></div>
						<!--developer-->
            <?php if ( $dev_name !== NULL ): ?>
              <div class="project-developer"><?php get_text('المطور العقاري','project developer'); ?><a href=<?php echo $dev_link; ?>><?php echo $dev_name; ?></a> </div>
            <?php endif; ?>

					</div>

				</div>

				<div class="col-md-7">

          <?php if( is_array($attachments) and count($attachments) > 0 ): ?>
            <div class="hero-banner">
  						<div id="project-slider">
                <?php foreach ($attachments as $galleryphoto) {
                    $photourl = wp_get_attachment_image_src($galleryphoto,'medium_large');
                    echo '<img loading="lazy" src='.$photourl[0].' alt="'.get_the_title().'" width="500" height="300">';
                } ?>
  						</div>

  						<div class="slider-nav">
                <?php foreach ($attachments as $galleryphoto) {
                    $photourl = wp_get_attachment_image_src($galleryphoto,'thumbnail');
                    echo '<img loading="lazy" class="item-slick" src='.$photourl[0].' alt="'.get_the_title().'" width="500" height="300">';
                } ?>
  						</div>
  					</div>
          <?php endif; ?>

				</div>
			</div>
		</div>
	</div>

  <?php
  $content = ob_get_clean();
  echo minify_html($content);


}


function get_projects_top_search()
{
  ?>
  <div class="topsearchbar">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <?php jawda_projects_search_box(); ?>
        </div>
      </div>
    </div>
  </div>
  <?php
}
