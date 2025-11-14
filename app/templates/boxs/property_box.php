<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects
----------------------------------------------------------------------------- */

function get_my_property_box($project_id){

  // phone number
  $phone = carbon_get_theme_option( 'jawda_phone' );

  // whatsapp Number
  $whatsapp = carbon_get_theme_option( 'jawda_whatsapp' );

  // whatsapp Link
  $whatsapplink = get_whatsapp_pro_link($whatsapp,$project_id);

  $price = carbon_get_post_meta( $project_id, 'jawda_price' );
  $bedrooms = carbon_get_post_meta( $project_id, 'jawda_bedrooms' );
  $bathrooms = carbon_get_post_meta( $project_id, 'jawda_bathrooms' );
  $garage = carbon_get_post_meta( $project_id, 'jawda_garage' );
  $size = carbon_get_post_meta( $project_id, 'jawda_size' );
  $year = carbon_get_post_meta( $project_id, 'jawda_year' );
  $location = carbon_get_post_meta( $project_id, 'jawda_location' );
  $installment = carbon_get_post_meta( $project_id, 'jawda_installment' );
  $down_payment = carbon_get_post_meta( $project_id, 'jawda_down_payment' );
  $finishing = carbon_get_post_meta( $project_id, 'jawda_finishing' );


  $property_city = featured_city_tag($project_id,'property_city');
  $property_status = featured_city_tag($project_id,'property_status');
  $post_type = featured_city_tag($project_id,'post_type');


  $img = get_the_post_thumbnail_url($project_id,'medium');
  $title = get_the_title($project_id);
  $url = get_the_permalink($project_id);
  ?>
  <div class="unit-box">
    <div class="unit-img">
      <a href="<?php echo $url; ?>">
        <img loading="lazy" src="<?php echo $img; ?>" width="500" height="300" alt="<?php echo $title; ?>" />
      </a>
      <span class="featured-cover"></span>
      <div class="unit-details">
        <?php if ( $bedrooms !== NULL AND $bedrooms != '' ): ?>
          <span><?php echo $bedrooms; ?> <i class="icon-bed"></i></span>
        <?php endif; ?>
        <?php if ( $bathrooms !== NULL AND $bathrooms != '' ): ?>
          <span><?php echo $bathrooms; ?> <i class="icon-bath"></i></span>
        <?php endif; ?>
        <?php if ( $garage !== NULL AND $garage != '' ): ?>
          <span><?php echo $garage; ?> <i class="icon-warehouse"></i></span>
        <?php endif; ?>
        <?php if ( $size !== NULL AND $size != '' ): ?>
          <span><?php echo $size; ?> <?php get_text('م²','m²'); ?><i class="icon-resize-full"></i></span>
        <?php endif; ?>

      </div>
      <div class="unit-tag">
        <?php echo $property_status; ?>
        <?php echo $post_type; ?>
      </div>
    </div>
    <div class="unit-data">
      <div class="unit-title"><a href="<?php echo $url; ?>"><?php echo $title; ?></a></div>
      <span class="unit-location"><i class="icon-location"></i> <?php echo $property_city; ?></span>
      <?php if ( is_numeric($price) ): ?>
        <div class="unitb-price"><span class="price-color"><?php echo number_format($price); ?> <?php txt('EGP'); ?></span></div>
      <?php endif; ?>
    </div>
    <div class="unit-payment">
      <?php if ( $down_payment !== NULL AND $down_payment != '' ): ?>
        <div class="payment-details">
          <div><?php get_text('المقدم','Down payment') ?></div>
          <b><?php echo $down_payment; ?></b>
        </div>
      <?php endif; ?>
      <?php if ( $finishing !== NULL AND $finishing != '' ): ?>
        <div class="payment-details">
          <div><?php get_text('تشطيب','Finishing'); ?></div>
          <b><?php echo $finishing; ?></b>
        </div>
      <?php endif; ?>
      <?php if ( $installment !== NULL AND $installment != '' ): ?>
        <div class="payment-details"><?php get_text('تقسيط على','Installment'); ?>  <?php echo $installment; ?></div>
      <?php endif; ?>
      <?php if ( $year !== NULL AND $year != '' ): ?>
        <div class="payment-details"><?php get_text('تاريخ التسليم','Delivery date'); ?>  <?php echo $year; ?></div>
      <?php endif; ?>
    </div>
    <div class="unit-contact">
      <a target="_blank" href="<?php echo $whatsapplink; ?>"><i class="icon-whatsapp" title="whatsapp"></i> <?php get_text('واتساب','whatsapp'); ?></a>
      <a href="tel:<?php echo $phone; ?>"><i class="icon-phone"></i> <?php get_text('اتصل','call'); ?></a>
      <a href="#contact"><i class="icon-mail-alt"></i> <?php get_text('رسالة','Message'); ?></a>
    </div>
  </div>

  <?php

}
