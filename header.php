<?php
/**
 * Copyright (c) 2018 Falicrea
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
 *
 * Contact: contact@falicrea.com
 */
?>
<!DOCTYPE html>
<html class="no-js" <?= language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <!--	<meta name="viewport" content="width=device-width, initial-scale=1">-->
  <meta name="viewport" content="width=500">
<!--  <link rel="apple-touch-icon" sizes="57x57" href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-57x57.png">-->
<!--  <link rel="apple-touch-icon" sizes="60x60" href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-60x60.png">-->
<!--  <link rel="apple-touch-icon" sizes="72x72" href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-72x72.png">-->
<!--  <link rel="apple-touch-icon" sizes="76x76" href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-76x76.png">-->
<!--  <link rel="apple-touch-icon" sizes="114x114"-->
<!--        href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-114x114.png">-->
<!--  <link rel="apple-touch-icon" sizes="120x120"-->
<!--        href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-120x120.png">-->
<!--  <link rel="apple-touch-icon" sizes="144x144"-->
<!--        href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-144x144.png">-->
<!--  <link rel="apple-touch-icon" sizes="152x152"-->
<!--        href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-152x152.png">-->
<!--  <link rel="apple-touch-icon" sizes="180x180"-->
<!--        href="--><?//= get_template_directory_uri() ?><!--/favicon/apple-icon-180x180.png">-->
<!--  <link rel="icon" type="image/png" sizes="192x192"-->
<!--        href="--><?//= get_template_directory_uri() ?><!--/favicon/android-icon-192x192.png">-->
<!--  <link rel="icon" type="image/png" sizes="32x32" href="--><?//= get_template_directory_uri() ?><!--/favicon/favicon-32x32.png">-->
<!--  <link rel="icon" type="image/png" sizes="96x96" href="--><?//= get_template_directory_uri() ?><!--/favicon/favicon-96x96.png">-->
<!--  <link rel="icon" type="image/png" sizes="16x16" href="--><?//= get_template_directory_uri() ?><!--/favicon/favicon-16x16.png">-->
<!--  <link rel="manifest" href="--><?//= get_template_directory_uri() ?><!--/favicon/manifest.json">-->
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
    .header .admin-dropdown-menu .admin-features-item img {
      display: block;
      margin-bottom: 16px;
      width: 30px;
      margin-left: auto;
      margin-right: auto;
      color: #ffffff;
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

    header.header .dropdown-user .dropdown-menu .dropdown-item,
    .dropdown-menu > li > a {
      background-color: #12a5d1;
      color: #ffffff;
      font-size: 12px;
    }

    header.header .dropdown-user .dropdown-item:hover,
    header.header .dropdown-user .dropdown-menu > li > a:hover {
      background-color: #f7f8f8;
      color: #16181b;
    }

    header.header .dropdown-user .dropdown-item:hover i {
      color: #16181b;
    }

    header.header .dropdown-user .dropdown-item > i {
      color: #ffffff;
      font-size: 11px;
    }

    .container-list-posts tbody {
      display: inline-table;
    }

    .alert-pink {
      font-size: 11.5px;
      margin-top: 3px;
      background-color: #bd1e54;
      border-color: #bd1e54;
    }
    .page-heading .page-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 18px;
      letter-spacing: 0.5px;
      font-weight: bold;
    }

    /* Espace client code */
    .tabs-line .nav-link:hover, .tabs-line .nav-link.active {
      color: #000000;
      border-bottom-color: #f39c12;
    }
    .nav-pills .nav-link, .nav-tabs .nav-link {
      color: #71808f;
      font-weight: 600;
      font-family: Montserrat, sans-serif;
    }
    .card {
      background-color: transparent;
    }

    tags-input .host {
      margin-top: 0px !important;
    }

    tags-input.ng-invalid .tags {
      box-shadow: 0 0 6px 0px rgba(255,0,0,.6);
    }
    tags-input .tags .tag-item {
      background: #18c5a9 !important;
      color: aliceblue !important;
      border: none !important;
    }
    tags-input .tags .tag-item {
      font: 11px "Poppins",Helvetica,Arial,sans-serif !important;
      line-height: normal;
      font-weight: bold !important;
      font-size: 12px !important;
      line-height: 27px !important;

    }
    tags-input .tags .tag-item .remove-button {
      color: #ffffff !important;
    }
    tags-input .tags .input {
      /* height: inherit !important; */
      font: 13px "Poppins",Helvetica,Arial,sans-serif !important;
      padding: 0 0 0 14px !important;
    }
    .admin-featured-desc {
      white-space: normal;
      font-size: 12px;
      margin-top: 10px;
      color: rgba(253, 254, 255, 0.65);
    }
  </style>
</head>
<body <?php body_class(); ?> >

<!--[if lt IE 8]>
<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
  your browser</a> to improve your experience.</p>
<![endif]-->
<div class="uk-offcanvas-content">
<div class="uk-section uk-section-small uk-padding-remove">

  <div class="uk-section uk-section-small uk-padding-remove header-top">
    <div class="uk-container-medium uk-container">
      <header class="header">
        <div uk-grid>
          <div class="uk-width-1-3@s uk-width-2-3">
            <div class="uk-flex">
              <div class="logo uk-margin-medium-right uk-flex" style="width: 30%">
                <a href="<?= home_url( '/' ) ?>" class="pt-4 pb-4">
                  <img src="<?= get_template_directory_uri() ?>/img/logo.png" class="uk-margin-auto-vertical"/>
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
          <div class="uk-width-2-3@s uk-width-1-3">
            <div class="uk-flex container-menu-header-top">
              <div class="menu-header-top uk-margin-auto-vertical uk-margin-auto-left">
                <ul class="uk-display-inline-block uk-margin-remove">
                  <?php
                  if ( ! is_user_logged_in() ) {
                    $page_login_id    = LOGIN_PAGE ? (int) LOGIN_PAGE : 0;
                    $oc_id = includes\object\jobServices::page_exists( 'Espace client' );
                    $oc_url = get_the_permalink($oc_id);
                    ?>
                    <li class="dropdown dropdown-user">
                      <a class="nav-link dropdown-toggle link btn btn-sm btn-blue" style="color: white" data-toggle="dropdown">
                        <span class="mr-2 text-uppercase p-relative" style="bottom: 3px;">
                          Se connecter
                        </span>
                        <i class="ti-user uk-text-large"></i>
                      </a>
                      <div class="dropdown-menu dropdown-arrow dropdown-menu-right admin-dropdown-menu">
                        <div class="dropdown-arrow"></div>
                        <div class="dropdown-header">
                          <div class="admin-menu-features">
                            <div class="admin-features-item">
                              <a class="text-uppercase" href="<?= home_url('/connexion/candidate?redir='.$oc_url) ?>">
  <!--                              <i class="fa fa-user-tie"></i>-->
                                <img src="<?= get_template_directory_uri() ?>/img/icons/user-solid.svg" />
                                <span class="text-white">PARTICULIERS</span>
                              </a>
                              <span class="text-center d-block font-weight-light admin-featured-desc">
                                Se connecter ou s'inscrire (demandeur d'emploi...)
                              </span>
                            </div>

                            <div class="admin-features-item">
                              <a class="text-uppercase" href="<?= home_url('/connexion/company?redir='.$oc_url) ?>">
                                <img src="<?= get_template_directory_uri() ?>/img/icons/user-tie-solid.svg" />
                                <span class="text-white">PROFESIONNELS</span>
                              </a>
                              <span class="text-center d-block font-weight-light pl-2 admin-featured-desc">
                                Se connecter ou s'inscrire ( Recruteur ou formateur )
                              </span>
                            </div>

                            
                          </div>
                        </div>

                      </div>
                    </li>
                    <?php
                  } else {
                    global $wp_roles;
                    $crUser             = wp_get_current_user();
                    $espace_client_link = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : '#no-link';
                    ?>
                    <li class="dropdown dropdown-user">
                      <a class="nav-link dropdown-toggle link btn btn-sm btn-blue" style="color: white" data-toggle="dropdown">
                        <span class="mr-2 text-uppercase p-relative" style="bottom: 3px;">
                          Mon compte
                        </span>
                        <i class="ti-user uk-text-large"></i>
                      </a>
                      <div class="dropdown-menu dropdown-arrow dropdown-menu-right admin-dropdown-menu">
                        <div class="dropdown-arrow"></div>
                        <a class="dropdown-item" href="<?= $espace_client_link ?>"><i class="ti-layout"></i>Espace Client</a>
                        <a class="dropdown-item" href="<?= wp_logout_url( home_url( '/' ) ) ?>"><i class="ti-shift-left"></i> Déconnecter</a>
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
