<?php
get_header();
?>
<style type="text/css">
  .it-404 {
    background-color: #fff;
    background-repeat: no-repeat;
    background-image: url(<?= get_template_directory_uri() ?>/img/search-document.svg);
    background-position: 85% 0px;
    background-size: contain;
  }

  .error-content {
    max-width: 400px;
    margin-left: 80px;
  }

  .error-code {
    font-size: 120px;
    color: #16b3d7;
  }
  .error-content .btn {
    background-color: #16b3d7;
    border-color: #16b3d7;
  }
</style>
  <div class="uk-section uk-section-transparent it-404">

    <div class="uk-container uk-container-small">
      <div class="error-content">
        <h1 class="error-code">404</h1>
        <h3 class="font-strong">NOT FOUND</h3>
        <p class="mb-4">Sorry, the page you were looking for could not found.</p>
        <div>
          <a class="btn btn-primary btn-fix" href="<?= home_url('/') ?>">ACCUEIL</a>
        </div>
      </div>
    </div>
  </div>
  </div>
<?php
get_footer();