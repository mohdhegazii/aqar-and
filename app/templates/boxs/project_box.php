<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects
----------------------------------------------------------------------------- */

function get_my_project_box($project_id){
  $price = carbon_get_post_meta( $project_id, 'jawda_price' );
  $project_city = featured_city_tag($project_id,'projects_area');
  $img = get_the_post_thumbnail_url($project_id,'medium');
  $title = get_the_title($project_id);
  $url = get_the_permalink($project_id);

  $display_title = $title;
  if ( mb_strlen( $title ) > 70 ) {
      $display_title = mb_substr( $title, 0, 70 ) . '...';
  }
  ?>
  <div class="related-box">
    <a href="<?php echo $url; ?>" class="related-img">
      <img loading="lazy" src="<?php echo $img; ?>" width="500" height="300" alt="<?php echo $title; ?>" /> </a>
    <div class="related-data">
      <div class="related-title"><a href="<?php echo $url; ?>"><?php echo $display_title; ?></a></div>
      <span class="project-location">
        <i class="icon-location"></i><?php echo $project_city; ?>
      </span>
      <?php
      $developer_terms = get_the_terms($project_id, 'projects_developer');
      if ( !empty($developer_terms) && !is_wp_error($developer_terms) ) {
          $developer_name = $developer_terms[0]->name;
          echo '<span class="project-developer"><i class="icon-building"></i>' . esc_html($developer_name) . '</span>';
      }
      ?>
      <div><a href="<?php echo $url; ?>" class="project-price"><?php get_text('اسعار تبدأ من','Prices starting from'); ?>
        <span><?php echo number_format( intval($price) ); ?> <?php get_text('ج.م','EGP'); ?></span>
      </a></div>
    </div>
    <a href="<?php echo $url; ?>" class="related-btn" aria-label="details"><i class="icon-left-big"></i></a>
  </div>
  <?php

}
