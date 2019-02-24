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
<!-- 
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
 -->
<!DOCTYPE html>
<html class="no-js" <?= language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <!--	<meta name="viewport" content="width=device-width, initial-scale=1">-->
  <meta name="viewport" content="width=500">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="<?= get_template_directory_uri() ?>/favicon/ms-icon-144x144.png">
  <meta name="theme-color" content="#ffffff">

  <!--[if lt IE 9]>
  <script src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/js/html5.js"></script>
  <![endif]-->

  <?php wp_head(); ?>

  <!-- All css files are included here. -->
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

    .btn-blue:focus, .btn-blue.focus, .btn-blue:hover,
    .btn-blue.active, .btn-blue:active,
    .btn-blue:disabled, .btn-blue.disabled,
    .show > .btn-blue.dropdown-toggle {
      background-color: #12a5d1;
      border-color: #12a5d1;
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
                    $User             = wp_get_current_user();
                    $espace_client_link = ESPACE_CLIENT_PAGE ? get_the_permalink( (int) ESPACE_CLIENT_PAGE ) : '#no-link';
                    $wallet_link = WALLET_PAGE ? get_the_permalink((int) WALLET_PAGE) : '#no-link';
                    $name = 'Administrateur';

                    if (in_array('candidate', $User->roles)) {
                      $Candidate = \includes\post\Candidate::get_candidate_by($User->ID);
                      $first_name = $Candidate->getFirstName();
                      $last_name = $Candidate->getLastName();
                      $name = $first_name . ' '.$last_name;
                    }

                    if (in_array('company', $User->roles)) {
                      $Company = \includes\post\Company::get_company_by($User->ID);
                      $name = $Company->name;
                    }
                    $wallet = \includes\post\Wallet::getInstance($User->ID, 'user_id', true);
                    $credit = $wallet->credit;

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
                        <div class="dropdown-header">
                          <div class="admin-avatar">
                            <img src="/wp-content/themes/itjob/img/user.png" alt="image" />
                          </div>
                          <div>
                            <h5 class="font-strong text-white"><a class="text-white" href="<?= $espace_client_link ?>"><?= $name ?></a> </h5>
                            <span class="text-white"><?= $User->user_email ?></span>
                          </div>
                        </div>
                        <div class="admin-menu-features">
                          <a class="admin-features-item" href="<?= $espace_client_link ?>">
                            <i class="ti-user"></i>
                            <span>MON COMPTE</span>
                          </a>
                          <a class="admin-features-item" href="<?= $wallet_link ?>">
                            <i class="ti-wallet"></i>
                            <span>CREDITS</span>
                          </a>
                          <a class="admin-features-item" href="<?= $espace_client_link ?>#!/manager/profil/settings">
                            <i class="ti-settings"></i>
                            <span>RÉGLAGES</span>
                          </a>
                        </div>
                        <div class="admin-menu-content">
                          <div class="text-muted mb-2">Mon portefeuille</div>
                          <div><i class="ti-wallet h1 mr-3 text-light"></i>
                            <span class="h1 text-success"><sup>¤</sup><?= $credit ?></span>
                          </div>
                          <div class="d-flex justify-content-between mt-2">
                            <a class="text-muted" href="<?= $wallet_link ?>">Credits</a>
                            <a class="d-flex align-items-center" href="<?= wp_logout_url( home_url( '/' ) ) ?>">Déconnecter<i class="ti-shift-right ml-2 font-20"></i></a>
                          </div>
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
