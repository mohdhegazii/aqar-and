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

// Head
get_my_project_header();

// project-main
get_my_project_main();

// related projects
get_my_related_projects();

// End loop
endwhile;

// Jawda header
get_my_footer();
