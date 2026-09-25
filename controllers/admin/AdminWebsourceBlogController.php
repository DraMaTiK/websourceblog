<?php
/**
 * Onglet parent « Blog » : redirige vers la liste des articles.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminWebsourceBlogController extends ModuleAdminController
{
    public function init()
    {
        parent::init();
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminWebsourceBlogPosts'));
    }
}
