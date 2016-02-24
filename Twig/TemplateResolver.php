<?php

namespace Sidus\AdminBundle\Twig;


use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;

class TemplateResolver
{
    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var string */
    protected $fallbackTemplate;

    /**
     * TemplateResolver constructor.
     * @param AdminConfigurationHandler $adminConfigurationHandler
     * @param \Twig_Environment $twig
     * @param string $fallbackTemplate
     */
    public function __construct(
        AdminConfigurationHandler $adminConfigurationHandler,
        \Twig_Environment $twig,
        $fallbackTemplate
    ) {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
        $this->twig = $twig;
        $this->fallbackTemplate = $fallbackTemplate;
    }


    /**
     * @param Admin $admin
     * @param Action $action
     * @return \Twig_Template
     * @throws \Twig_Error_Syntax|\Twig_Error_Loader|\UnexpectedValueException
     */
    public function getTemplate(Admin $admin = null, Action $action = null)
    {
        if (!$admin) {
            $admin = $this->adminConfigurationHandler->getCurrentAdmin();
        }
        if (!$action) {
            $action = $admin->getCurrentAction();
        }
        $templateType = 'html'; // @todo inject type from Request ?

        $customTemplate = "{$admin->getController()}:{$action->getCode()}.{$templateType}.twig";
        try {
            return $this->twig->loadTemplate($customTemplate);
        } catch (\Twig_Error_Loader $e) {}

        $fallbackTemplate = "{$this->getFallbackTemplate()}:{$action->getCode()}.html.twig";
        return $this->twig->loadTemplate($fallbackTemplate);
    }

    /**
     * @return string
     * @throws \UnexpectedValueException
     */
    public function getFallbackTemplate()
    {
        if (!$this->fallbackTemplate) {
            throw new \UnexpectedValueException("Missing option 'fallback_template' in global admin configuration, ".
            'you must either specify or create a template for each action or set the fallback_template option');
        }
        return $this->fallbackTemplate;
    }
}
