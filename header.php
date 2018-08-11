<?php
/**
 * Copyright (c) 2018 Tiafeno Finel
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files, to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
?>
<!DOCTYPE html>
<html class="no-js" <?= language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
<!--	<meta name="viewport" content="width=device-width, initial-scale=1">-->
	<meta name="viewport" content="width=500">
	<link rel="apple-touch-icon" sizes="57x57" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="<?= get_template_directory_uri() ?>/favicon/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="<?= get_template_directory_uri() ?>/favicon/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="<?= get_template_directory_uri() ?>/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="<?= get_template_directory_uri() ?>/favicon/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="<?= get_template_directory_uri() ?>/favicon/favicon-16x16.png">
	<link rel="manifest" href="<?= get_template_directory_uri() ?>/favicon/manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="<?= get_template_directory_uri() ?>/favicon/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">

	<!--[if lt IE 9]>
	<script src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/js/html5.js"></script>
	<![endif]-->

	<!-- Place favicon.ico in the root directory -->

	<!-- All css files are included here. -->
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> >

<!--[if lt IE 8]>
<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
	your browser</a> to improve your experience.</p>
<![endif]-->

<div class="uk-section uk-section-large">
  <div class="uk-container-small uk-container">
    <header class="header-top">
      <div uk-grid>
        <div class="uk-width-1-4">
          <div class="logo">
          </div>
          <div class="header-offcanvas">

          </div>
        </div>
        <div class="uk-width-3-4">
          <div class="menu-header-top">
            <ul class="uk-display-inline-block">
              <li><a href="#">S'enregister</a></li>
              <li><a href="#">Connexion</a></li>
            </ul>
          </div>
        </div>
      </div>
    </header>
  </div>
</div>