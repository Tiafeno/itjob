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
        echo "<div class=\"ibox\">
  <form action=\"javascript:;\">
    <div class=\"ibox-head\">
      <div class=\"ibox-title\">Multi Column form</div>
    </div>
    <div class=\"ibox-body\">
      <div class=\"row\">
        <div class=\"col-md-6\">
          <div class=\"form-group mb-4\">
            <label>Full Name</label>
            <input class=\"form-control\" type=\"text\" placeholder=\"Enter Full Name\">
          </div>
          <div class=\"form-group mb-4\">
            <label>Email</label>
            <input class=\"form-control\" type=\"text\" placeholder=\"Enter Email\">
          </div>
          <div class=\"form-group mb-4\">
            <label>Date of Birth</label>
            <input class=\"form-control\" type=\"text\" placeholder=\"Enter Date of Birth\">
            <span class=\"help-block\">Please Enter your date of birth.</span>
          </div>
        </div>
        <div class=\"col-md-6\">
          <div class=\"form-group mb-4\">
            <label>Location</label>
            <div class=\"input-group-icon input-group-icon-left\">
              <span class=\"input-icon input-icon-left\"><i class=\"ti-location-pin font-16\"></i></span>
              <input class=\"form-control\" type=\"text\" placeholder=\"Enter Location\">
            </div>
          </div>
          <div class=\"form-group mb-4\">
            <label>Password</label>
            <div class=\"input-group-icon input-group-icon-left\">
              <span class=\"input-icon input-icon-left\"><i class=\"ti-lock\"></i></span>
              <input class=\"form-control\" type=\"password\" placeholder=\"Enter Password\">
            </div>
          </div>
          <div class=\"form-group mb-4\">
            <label>Phone number</label>
            <div class=\"input-group\">
              <div class=\"input-group-btn\">
                <button class=\"btn btn-outline-secondary dropdown-toggle\" data-toggle=\"dropdown\">+61<i class=\"fa fa-angle-down ml-1\"></i></button>
                <div class=\"dropdown-menu\">
                  <a class=\"dropdown-item\" href=\"javascript:;\">+61</a>
                  <a class=\"dropdown-item\" href=\"javascript:;\">+1</a>
                  <a class=\"dropdown-item\" href=\"javascript:;\">+7</a>
                </div>
              </div>
              <input class=\"form-control\" type=\"text\" placeholder=\"Enter Phone\">
            </div>
            <span class=\"help-block\">It will be required to verify your account.</span>
          </div>
        </div>
      </div>
      <div class=\"form-group mb-0\">
        <label>Account Type</label>
        <div class=\"mt-1\">
          <label class=\"radio radio-inline radio-grey radio-primary\">
            <input type=\"radio\" name=\"d\" checked>
            <span class=\"input-span\"></span>Personal</label>
          <label class=\"radio radio-inline radio-grey radio-primary\">
            <input type=\"radio\" name=\"d\">
            <span class=\"input-span\"></span>Corporate</label>
        </div>
        <span class=\"help-block\">Select one of 2 types of accounts.</span>
      </div>
    </div>
    <div class=\"ibox-footer\">
      <button class=\"btn btn-primary mr-2\" type=\"button\">Submit</button>
      <button class=\"btn btn-outline-secondary\" type=\"reset\">Cancel</button>
    </div>
  </form>
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
        return new Twig_Source("<div class=\"ibox\">
  <form action=\"javascript:;\">
    <div class=\"ibox-head\">
      <div class=\"ibox-title\">Multi Column form</div>
    </div>
    <div class=\"ibox-body\">
      <div class=\"row\">
        <div class=\"col-md-6\">
          <div class=\"form-group mb-4\">
            <label>Full Name</label>
            <input class=\"form-control\" type=\"text\" placeholder=\"Enter Full Name\">
          </div>
          <div class=\"form-group mb-4\">
            <label>Email</label>
            <input class=\"form-control\" type=\"text\" placeholder=\"Enter Email\">
          </div>
          <div class=\"form-group mb-4\">
            <label>Date of Birth</label>
            <input class=\"form-control\" type=\"text\" placeholder=\"Enter Date of Birth\">
            <span class=\"help-block\">Please Enter your date of birth.</span>
          </div>
        </div>
        <div class=\"col-md-6\">
          <div class=\"form-group mb-4\">
            <label>Location</label>
            <div class=\"input-group-icon input-group-icon-left\">
              <span class=\"input-icon input-icon-left\"><i class=\"ti-location-pin font-16\"></i></span>
              <input class=\"form-control\" type=\"text\" placeholder=\"Enter Location\">
            </div>
          </div>
          <div class=\"form-group mb-4\">
            <label>Password</label>
            <div class=\"input-group-icon input-group-icon-left\">
              <span class=\"input-icon input-icon-left\"><i class=\"ti-lock\"></i></span>
              <input class=\"form-control\" type=\"password\" placeholder=\"Enter Password\">
            </div>
          </div>
          <div class=\"form-group mb-4\">
            <label>Phone number</label>
            <div class=\"input-group\">
              <div class=\"input-group-btn\">
                <button class=\"btn btn-outline-secondary dropdown-toggle\" data-toggle=\"dropdown\">+61<i class=\"fa fa-angle-down ml-1\"></i></button>
                <div class=\"dropdown-menu\">
                  <a class=\"dropdown-item\" href=\"javascript:;\">+61</a>
                  <a class=\"dropdown-item\" href=\"javascript:;\">+1</a>
                  <a class=\"dropdown-item\" href=\"javascript:;\">+7</a>
                </div>
              </div>
              <input class=\"form-control\" type=\"text\" placeholder=\"Enter Phone\">
            </div>
            <span class=\"help-block\">It will be required to verify your account.</span>
          </div>
        </div>
      </div>
      <div class=\"form-group mb-0\">
        <label>Account Type</label>
        <div class=\"mt-1\">
          <label class=\"radio radio-inline radio-grey radio-primary\">
            <input type=\"radio\" name=\"d\" checked>
            <span class=\"input-span\"></span>Personal</label>
          <label class=\"radio radio-inline radio-grey radio-primary\">
            <input type=\"radio\" name=\"d\">
            <span class=\"input-span\"></span>Corporate</label>
        </div>
        <span class=\"help-block\">Select one of 2 types of accounts.</span>
      </div>
    </div>
    <div class=\"ibox-footer\">
      <button class=\"btn btn-primary mr-2\" type=\"button\">Submit</button>
      <button class=\"btn btn-outline-secondary\" type=\"reset\">Cancel</button>
    </div>
  </form>
</div>", "@SC/import-csv.html.twig", "C:\\xampp\\htdocs\\managna\\wp-content\\themes\\itjob\\templates\\shortcodes\\import-csv.html.twig");
    }
}
