<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Front Page
----------------------------------------------------------------------------- */

// Jawda header
get_my_header();

// Region Loop
while ( have_posts() ) : the_post();

// project-main
get_my_catalogs_main();

// End loop
endwhile;

// Jawda header
get_my_footer();
