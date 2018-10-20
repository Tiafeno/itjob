<?php

/* @SC/import-csv.html.twig */
class __TwigTemplate_9d05d00ed17f9da98300e35cf212f3fc755a5e08d4b57f056c7e3fa07abe9163 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<style type=\"text/css\">
  .import-csv-container {
    max-width: 450px;
    height: auto;
    margin: 0px auto 50px;
  }
  .form-control {
    font-family: Montserrat, sans-serif;
    font-size: 12px;
  }
</style>
<div class=\"page-header mt-5\" ng-app=\"importCSVModule\">
  <ui-view>
    <div class=\"mt-5 pt-5\">
      <h4 class=\"font-light text-center\">Importer un CSV</h4>
      <p class=\"text-center mb-5\">Chargement du formulaire...</p>
    </div>
  </ui-view>
</div>";
    }

    public function getTemplateName()
    {
        return "@SC/import-csv.html.twig";
    }

    public function getDebugInfo()
    {
        return array (  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("<style type=\"text/css\">
  .import-csv-container {
    max-width: 450px;
    height: auto;
    margin: 0px auto 50px;
  }
  .form-control {
    font-family: Montserrat, sans-serif;
    font-size: 12px;
  }
</style>
<div class=\"page-header mt-5\" ng-app=\"importCSVModule\">
  <ui-view>
    <div class=\"mt-5 pt-5\">
      <h4 class=\"font-light text-center\">Importer un CSV</h4>
      <p class=\"text-center mb-5\">Chargement du formulaire...</p>
    </div>
  </ui-view>
</div>", "@SC/import-csv.html.twig", "C:\\xampp\\htdocs\\managna\\wp-content\\themes\\itjob\\templates\\shortcodes\\import-csv.html.twig");
    }
}
