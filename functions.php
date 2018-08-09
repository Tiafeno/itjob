<?php
/**
 *   Copyright (c) 2018, Falicrea
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy
 *   of this software and associated documentation files, to deal
 *   in the Software without restriction, including without limitation the rights
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *   copies of the Software, and to permit persons to whom the Software is
 *   furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in all
 *   copies or substantial portions of the Software.
 */

define( '__SITENAME__', 'itJob' );
define( '__google_api__', base64_decode( 'QUl6YVN5Qng3LVJKbGlwbWU0YzMtTGFWUk5oRnhiV19xWG5DUXhj' ) );
define( 'TWIG_TEMPLATE_PATH', get_template_directory() . '/templates' );
$theme = wp_get_theme( 'itjob' );

$itJob = (object) [
	'version'  => $theme->get( 'Version' ),
	'root'     => require 'includes/class/class-itjob.php'
];

require 'includes/class/class-menu-walker.php';
require 'includes/filters/engine-filters.php';
require 'api/itjob-api.php';
require 'jobs/itjob-cron.php';

// Autoload composer libraries
require 'composer/vendor/autoload.php';

try {
	$loader = new Twig_Loader_Filesystem();
	$loader->addPath( TWIG_TEMPLATE_PATH . '/vc', 'VC' );

	/** @var Object $Engine */
	$Engine = new Twig_Environment( $loader, array(
		'debug'       => WP_DEBUG,
		'cache'       => TWIG_TEMPLATE_PATH . '/cache',
		'auto_reload' => WP_DEBUG
	) );

	// Ajouter des filtres
	itjob_filter_engine( $Engine );

} catch ( Twig_Error_Loader $e ) {
	die( $e->getRawMessage() );
}

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'twentyfifteen' );
	load_theme_textdomain( __SITENAME__, get_template_directory() . '/languages' );

	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'category-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', array(
		'height'     => 100,
		'width'      => 250,
		'flex-width' => true,
	) );

	// Register menu location
	register_nav_menus( array(
		'primary'  => __( 'Primary Menu', 'twentyfifteen' ),
		'menu-top' => __( 'Top Menu', __SITENAME__ )
	) );
} );

if( function_exists('acf_add_options_page') ) {
	$parent = acf_add_options_page(array(
		'page_title' 	=> 'General Settings',
		'menu_title' 	=> 'itJob Settings',
		'capability' => 'edit_posts',
		'redirect' 		=> false
	));
}
