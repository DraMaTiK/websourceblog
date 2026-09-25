<?php
/**
 * Administration des articles du blog.
 *
 * Le champ « Date de publication » est libre : une date passée antidate l'article,
 * une date future le programme (il n'apparaît qu'à partir de cette date).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/WsbAdminController.php';

class AdminWebsourceBlogPostsController extends WsbAdminController
{
    public function __construct()
    {
        $this->table = 'wsb_post';
        $this->className = 'WsbPost';
        $this->identifier = 'id_wsb_post';
        $this->lang = true;
        $this->bootstrap = true;
        $this->_defaultOrderBy = 'date_publish';
        $this->_defaultOrderWay = 'DESC';
        $this->context = Context::getContext();

        $this->_select = 'cl.name AS category_name,
            IF(a.active = 1 AND a.date_publish <= NOW(), 1, IF(a.active = 1, 2, 0)) AS state';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'wsb_category_lang` cl ON (cl.id_wsb_category = a.id_wsb_category AND cl.id_lang = ' . (int) $this->context->language->id . ')';

        $this->fields_list = [
            'id_wsb_post' => ['title' => $this->trans('ID', [], 'Admin.Global'), 'class' => 'fixed-width-xs', 'align' => 'center'],
            'title' => ['title' => 'Titre', 'filter_key' => 'b!title'],
            'category_name' => ['title' => 'Catégorie', 'filter_key' => 'cl!name'],
            'date_publish' => ['title' => 'Date de publication', 'type' => 'datetime', 'align' => 'center'],
            'state' => ['title' => 'Statut', 'align' => 'center', 'search' => false, 'orderby' => false, 'callback' => 'renderState', 'class' => 'fixed-width-md'],
            'views' => ['title' => 'Vues', 'align' => 'center', 'class' => 'fixed-width-xs'],
            'featured' => ['title' => 'À la une', 'type' => 'bool', 'align' => 'center', 'class' => 'fixed-width-sm', 'callback' => 'renderFlag', 'orderby' => false],
            'active' => ['title' => 'Actif', 'active' => 'status', 'type' => 'bool', 'align' => 'center', 'class' => 'fixed-width-sm', 'orderby' => false],
        ];
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = ['delete' => ['text' => 'Supprimer la sélection', 'confirm' => 'Supprimer les articles sélectionnés ?']];

        parent::__construct();
    }

    public function renderState($value)
    {
        $labels = [0 => ['Brouillon', 'default'], 1 => ['Publié', 'success'], 2 => ['Programmé', 'warning']];
        $l = $labels[(int) $value];

        return '<span class="label label-' . $l[1] . '">' . $l[0] . '</span>';
    }

    public function renderFlag($value)
    {
        return $value ? '<i class="icon-star" style="color:#e6a700"></i>' : '';
    }

    public function renderForm()
    {
        $idLang = (int) $this->context->language->id;
        $cats = WsbCategory::getOptions($idLang);
        if (!$cats) {
            $this->errors[] = 'Créez d\'abord au moins une catégorie (Blog > Catégories).';
        }
        $obj = $this->loadObject(true);
        $imageUrl = $obj && $obj->image ? WsbImage::url($obj->image, 480) : '';
        $imageHtml = $imageUrl ? '<img src="' . $imageUrl . '" alt="" style="max-width:280px;height:auto;border-radius:4px">' : '';

        $this->fields_form = [
            'legend' => ['title' => 'Article de blog', 'icon' => 'icon-pencil'],
            'input' => [
                ['type' => 'text', 'label' => 'Titre', 'name' => 'title', 'lang' => true, 'required' => true],
                ['type' => 'text', 'label' => 'URL (réécriture)', 'name' => 'link_rewrite', 'lang' => true, 'hint' => 'Laissez vide pour la générer depuis le titre. Elle est rendue unique automatiquement.'],
                ['type' => 'select', 'label' => 'Catégorie', 'name' => 'id_wsb_category', 'required' => true, 'options' => ['query' => $cats, 'id' => 'id', 'name' => 'name']],
                ['type' => 'textarea', 'label' => 'Extrait', 'name' => 'excerpt', 'lang' => true, 'rows' => 3, 'hint' => 'Résumé affiché dans les listes et le carousel. Vide : extrait généré depuis le contenu.'],
                ['type' => 'textarea', 'label' => 'Contenu', 'name' => 'content', 'lang' => true, 'autoload_rte' => true, 'cols' => 60, 'rows' => 20],
                ['type' => 'file', 'label' => 'Image de couverture', 'name' => 'image', 'display_image' => false, 'desc' => 'JPG, PNG, GIF, WEBP ou AVIF, 8 Mo max. Recadrée en 16/9 et convertie en AVIF si l\'option est active.' . ($imageHtml ? '<br>' . $imageHtml : '')],
                ['type' => 'checkbox', 'label' => 'Image', 'name' => 'delete_image', 'values' => ['query' => [['id' => '1', 'name' => 'Supprimer l\'image actuelle']], 'id' => 'id', 'name' => 'name']],
                ['type' => 'text', 'label' => 'Texte alternatif de l\'image', 'name' => 'image_alt', 'lang' => true, 'hint' => 'Important pour l\'accessibilité et le SEO.'],
                ['type' => 'text', 'label' => 'Tags', 'name' => 'wsb_tags', 'hint' => 'Séparés par des virgules. Les tags inconnus sont créés automatiquement.'],
                ['type' => 'text', 'label' => 'Meta title', 'name' => 'meta_title', 'lang' => true, 'hint' => 'Titre SEO (≈ 60 caractères). Vide : le titre de l\'article.'],
                ['type' => 'textarea', 'label' => 'Meta description', 'name' => 'meta_description', 'lang' => true, 'rows' => 3, 'hint' => 'Description SEO (≈ 155 caractères). Vide : l\'extrait.'],
                ['type' => 'datetime', 'label' => 'Date de publication', 'name' => 'date_publish', 'required' => true, 'hint' => 'Modifiable : une date passée antidate l\'article, une date future le programme (visible à partir de cette date).'],
                ['type' => 'text', 'label' => 'Auteur affiché', 'name' => 'author_name', 'class' => 'fixed-width-xxl'],
                ['type' => 'switch', 'label' => 'À la une', 'name' => 'featured', 'is_bool' => true, 'values' => [
                    ['id' => 'featured_on', 'value' => 1, 'label' => 'Oui'], ['id' => 'featured_off', 'value' => 0, 'label' => 'Non'],
                ]],
                ['type' => 'switch', 'label' => 'Actif', 'name' => 'active', 'is_bool' => true, 'values' => [
                    ['id' => 'active_on', 'value' => 1, 'label' => 'Oui'], ['id' => 'active_off', 'value' => 0, 'label' => 'Non'],
                ]],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
        ];

        if (!$obj->id) {
            $this->fields_value['active'] = 1;
            $this->fields_value['date_publish'] = date('Y-m-d H:i:s');
            $this->fields_value['author_name'] = $this->context->employee ? trim($this->context->employee->firstname . ' ' . $this->context->employee->lastname) : '';
            $this->fields_value['wsb_tags'] = '';
        } else {
            $this->fields_value['wsb_tags'] = WsbPost::getTagsAsString((int) $obj->id, (int) Configuration::get('PS_LANG_DEFAULT'));
        }

        return parent::renderForm();
    }

    public function processSave()
    {
        $this->prepareLangPost(['title', 'excerpt', 'content', 'meta_title', 'meta_description', 'image_alt'], 'title', 'wsb_post', 'id_wsb_post');
        $date = trim((string) Tools::getValue('date_publish'));
        if ($date === '' || !Validate::isDate($date)) {
            $_POST['date_publish'] = date('Y-m-d H:i:s');
        } elseif (strlen($date) === 10) {
            $_POST['date_publish'] = $date . ' 00:00:00';
        }

        return parent::processSave();
    }

    private function afterSave($object)
    {
        $errors = [];
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        if (Tools::getValue('delete_image_1')) {
            WsbImage::deleteFiles($object->image);
            $object->image = '';
            $object->update();
        }
        $base = WsbImage::handleUpload('image', 'post-' . (int) $object->id . '-' . time(), (bool) Configuration::get('WSB_AVIF'), $errors);
        if ($base) {
            if ($object->image) {
                WsbImage::deleteFiles($object->image);
            }
            $object->image = $base;
            $object->update();
        }
        WsbPost::setTagsFromString((int) $object->id, (string) Tools::getValue('wsb_tags'), $idLang);
        foreach ($errors as $e) {
            $this->errors[] = $e;
        }

        return true;
    }

    protected function afterAdd($object)
    {
        return $this->afterSave($object);
    }

    protected function afterUpdate($object)
    {
        return $this->afterSave($object);
    }

    /** Action « Voir » : ouvre l'article en front (aperçu avec jeton si non publié). */
    public function displayViewLink($token, $id, $name = null)
    {
        $idLang = (int) $this->context->language->id;
        $row = Db::getInstance()->getRow('SELECT pl.link_rewrite, cl.link_rewrite AS category_rewrite
            FROM `' . _DB_PREFIX_ . 'wsb_post` p
            JOIN `' . _DB_PREFIX_ . 'wsb_post_lang` pl ON pl.id_wsb_post = p.id_wsb_post AND pl.id_lang = ' . $idLang . '
            JOIN `' . _DB_PREFIX_ . 'wsb_category_lang` cl ON cl.id_wsb_category = p.id_wsb_category AND cl.id_lang = ' . $idLang . '
            WHERE p.id_wsb_post = ' . (int) $id);
        if (!$row) {
            return '';
        }
        $url = $this->module->getPostLink($row['category_rewrite'], $row['link_rewrite'], $idLang);
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'preview=' . WsbPost::previewToken((int) $id);

        return '<a class="btn btn-default" href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" rel="noopener" title="Voir"><i class="icon-eye"></i> Voir</a>';
    }
}
