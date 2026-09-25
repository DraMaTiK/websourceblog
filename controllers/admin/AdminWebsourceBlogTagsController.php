<?php
/**
 * Administration des tags du blog.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/WsbAdminController.php';

class AdminWebsourceBlogTagsController extends WsbAdminController
{
    public function __construct()
    {
        $this->table = 'wsb_tag';
        $this->className = 'WsbTag';
        $this->identifier = 'id_wsb_tag';
        $this->lang = true;
        $this->bootstrap = true;
        $this->_defaultOrderBy = 'name';
        $this->_defaultOrderWay = 'ASC';
        $this->context = Context::getContext();
        $this->_select = '(SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'wsb_post_tag` pt WHERE pt.id_wsb_tag = a.id_wsb_tag) AS nb_posts';

        $this->fields_list = [
            'id_wsb_tag' => ['title' => $this->trans('ID', [], 'Admin.Global'), 'class' => 'fixed-width-xs', 'align' => 'center'],
            'name' => ['title' => 'Tag', 'filter_key' => 'b!name'],
            'link_rewrite' => ['title' => 'URL', 'filter_key' => 'b!link_rewrite'],
            'nb_posts' => ['title' => 'Articles', 'align' => 'center', 'search' => false, 'orderby' => false],
        ];
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = ['delete' => ['text' => 'Supprimer la sélection', 'confirm' => 'Supprimer les tags sélectionnés ?']];

        parent::__construct();
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => ['title' => 'Tag', 'icon' => 'icon-tag'],
            'input' => [
                ['type' => 'text', 'label' => 'Nom', 'name' => 'name', 'lang' => true, 'required' => true],
                ['type' => 'text', 'label' => 'URL (réécriture)', 'name' => 'link_rewrite', 'lang' => true, 'hint' => 'Laissez vide pour la générer depuis le nom.'],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
        ];

        return parent::renderForm();
    }

    public function processSave()
    {
        $this->prepareLangPost(['name'], 'name', 'wsb_tag', 'id_wsb_tag');

        return parent::processSave();
    }
}
