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
  <link rel="apple-touch-icon" sizes="114x114"
        href="<?= get_template_directory_uri() ?>/favicon/apple-icon-114x114.png">
  <link rel="apple-touch-icon" sizes="120x120"
        href="<?= get_template_directory_uri() ?>/favicon/apple-icon-120x120.png">
  <link rel="apple-touch-icon" sizes="144x144"
        href="<?= get_template_directory_uri() ?>/favicon/apple-icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152"
        href="<?= get_template_directory_uri() ?>/favicon/apple-icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180"
        href="<?= get_template_directory_uri() ?>/favicon/apple-icon-180x180.png">
  <link rel="icon" type="image/png" sizes="192x192"
        href="<?= get_template_directory_uri() ?>/favicon/android-icon-192x192.png">
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
  <style type="text/css">
    .header-top {
      background-color: #fff;
      -webkit-box-shadow: 0 5px 20px #d6dee4;
      box-shadow: 0 5px 20px #d6dee4;
    }
    .logo img.uk-logo {
      width: 80%;
    }
    .menu-header-top ul li {
      display: inline-block;
      margin-left: 10px;
    }
    .container-menu-header-top {
      height: 100%;
    }
    .header-offcanvas > .btn {
      background: transparent;
      color: #a7a9ac;
    }
    .header-offcanvas > .btn i {
      font-size: 26px;
      color: #a7a9ac;
    }
    .btn.dropdown-arrow:after {
      margin-left: 0 !important;
    }
  </style>
</head>
<body <?php body_class(); ?> >

<!--[if lt IE 8]>
<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
  your browser</a> to improve your experience.</p>
<![endif]-->

<div class="uk-section uk-section-small uk-padding-remove uk-offcanvas-content">

  <div class="uk-section uk-section-small header-top">
    <div class="uk-container-medium uk-container">
      <header>
        <div uk-grid>
          <div class="uk-width-1-3@s uk-width-2-3">
            <div class="uk-flex">
              <div class="logo uk-margin-medium-right" style="width: 30%">
                <a href="<?= home_url( '/' ) ?>" class="d-block p-relative">
                  <img src="<?= get_template_directory_uri() ?>/img/logo.png"/>
                </a>
              </div>
              <div class="header-offcanvas uk-flex">
                <button class="btn uk-margin-auto-vertical" uk-toggle="target: #offcanvas-push">
                  <span class="btn-icon"><i class="ti-align-left"></i>MENU</span>
                </button>

                <div id="offcanvas-push" uk-offcanvas="mode: push; overlay: true">
                  <div class="uk-offcanvas-bar">
                    <button class="uk-offcanvas-close" type="button" uk-close></button>
<!--                    <h3>MENU</h3>-->
                    <?php get_template_part('partials/principal', 'menu') ?>
                  </div>
                </div>

              </div>
            </div>

          </div>
          <div class="uk-width-2-3@s uk-width-1-1">
            <div class="uk-flex container-menu-header-top">
              <div class="menu-header-top uk-margin-auto-vertical uk-margin-auto-left">
                <ul class="uk-display-inline-block uk-margin-remove">
                  <?php
                  if ( ! is_user_logged_in() ) {
                    $register_link = REGISTER_PAGE ? get_the_permalink( (int) REGISTER_PAGE ) : '#no-link';
                    $login_link    = LOGIN_PAGE ? get_the_permalink( (int) LOGIN_PAGE ) : '#no-link';
                    echo sprintf( '<li><a class="btn btn-outline-primary btn-fix btn-thick" href="%s">S\'enregister</a></li>', $register_link );
                    echo sprintf( '<li><a class="btn btn-outline-primary btn-fix btn-thick" href="%s">%s</a></li><li>', $login_link, 'Connexion' );
                  } else {
                    $crUser             = wp_get_current_user();
                    $espace_client_link = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : '#no-link';
                    ?>
                    <li>
                      <div class="btn-group">
                        <a class="btn btn-outline-primary"
                           href="<?= $espace_client_link ?>"><?= ucfirst($crUser->display_name) ?></a>
                        <button class="btn btn-outline-primary dropdown-toggle dropdown-arrow"
                                data-toggle="dropdown"></button>
                        <div class="dropdown-menu dropdown-menu-right">
                          <a class="dropdown-item" href="<?= wp_logout_url( home_url( '/' ) ) ?>">Se déconnecter</a>
                        </div>
                      </div>
                    </li>
                    <?php
                  }
                  ?>

                </ul>
              </div>
            </div>

          </div>
        </div>
      </header>
    </div>
  </div>
