<?php
/**
 * Administration des catégories du blog.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/WsbAdminController.php';

class AdminWebsourceBlogCategoriesController extends WsbAdminController
{
    public function __construct()
    {
        $this->table = 'wsb_category';
        $this->className = 'WsbCategory';
        $this->identifier = 'id_wsb_category';
        $this->lang = true;
        $this->bootstrap = true;
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
        $this->context = Context::getContext();

        $this->_select = '(SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'wsb_post` p WHERE p.id_wsb_category = a.id_wsb_category) AS nb_posts';

        $this->fields_list = [
            'id_wsb_category' => ['title' => $this->trans('ID', [], 'Admin.Global'), 'class' => 'fixed-width-xs', 'align' => 'center'],
            'name' => ['title' => 'Nom', 'filter_key' => 'b!name'],
            'link_rewrite' => ['title' => 'URL', 'filter_key' => 'b!link_rewrite'],
            'nb_posts' => ['title' => 'Articles', 'align' => 'center', 'search' => false, 'orderby' => false],
            'position' => ['title' => 'Position', 'align' => 'center', 'class' => 'fixed-width-sm'],
            'active' => ['title' => 'Actif', 'active' => 'status', 'type' => 'bool', 'align' => 'center', 'class' => 'fixed-width-sm', 'orderby' => false],
        ];
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = ['delete' => ['text' => 'Supprimer la sélection', 'confirm' => 'Supprimer les catégories sélectionnées ?']];

        parent::__construct();
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => ['title' => 'Catégorie du blog', 'icon' => 'icon-folder-open'],
            'input' => [
                ['type' => 'text', 'label' => 'Nom', 'name' => 'name', 'lang' => true, 'required' => true],
                ['type' => 'text', 'label' => 'URL (réécriture)', 'name' => 'link_rewrite', 'lang' => true, 'hint' => 'Laissez vide pour la générer depuis le nom.'],
                ['type' => 'textarea', 'label' => 'Description', 'name' => 'description', 'lang' => true, 'autoload_rte' => true, 'cols' => 60, 'rows' => 6],
                ['type' => 'text', 'label' => 'Meta title', 'name' => 'meta_title', 'lang' => true, 'hint' => 'Titre SEO (≈ 60 caractères).'],
                ['type' => 'textarea', 'label' => 'Meta description', 'name' => 'meta_description', 'lang' => true, 'rows' => 3, 'hint' => 'Description SEO (≈ 155 caractères).'],
                ['type' => 'switch', 'label' => 'Actif', 'name' => 'active', 'is_bool' => true, 'values' => [
                    ['id' => 'active_on', 'value' => 1, 'label' => 'Oui'], ['id' => 'active_off', 'value' => 0, 'label' => 'Non'],
                ]],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
        ];
        if (!$this->object) {
            $this->fields_value['active'] = 1;
        }

        return parent::renderForm();
    }

    public function processSave()
    {
        $this->prepareLangPost(['name', 'description', 'meta_title', 'meta_description'], 'name', 'wsb_category', 'id_wsb_category');

        return parent::processSave();
    }

    public function processDelete()
    {
        $c = new WsbCategory((int) Tools::getValue($this->identifier));
        if (Validate::isLoadedObject($c) && !$c->delete()) {
            $this->errors[] = 'Cette catégorie contient encore des articles : déplacez ou supprimez-les d\'abord.';

            return false;
        }

        return true;
    }
}
