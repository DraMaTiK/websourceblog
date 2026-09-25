<?php
/**
 * Catégorie de blog.
 *
 * @author    Websource
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WsbCategory extends ObjectModel
{
    public $id_wsb_category;
    public $active = 1;
    public $position = 0;
    public $date_add;
    public $date_upd;

    /** @var string|array */
    public $name;
    public $link_rewrite;
    public $description;
    public $meta_title;
    public $meta_description;

    public static $definition = [
        'table' => 'wsb_category',
        'primary' => 'id_wsb_category',
        'multilang' => true,
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            // Multilangue
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
            'link_rewrite' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128],
            'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 512],
        ],
    ];

    public function add($auto_date = true, $null_values = false)
    {
        if (!$this->position) {
            $this->position = (int) Db::getInstance()->getValue('SELECT COALESCE(MAX(position), 0) + 1 FROM `' . _DB_PREFIX_ . 'wsb_category`');
        }

        return parent::add($auto_date, $null_values);
    }

    /**
     * Catégories actives (avec au moins 1 article publié si $withCount).
     */
    public static function getActive($idLang, $onlyWithPosts = false)
    {
        $sql = 'SELECT c.*, cl.name, cl.link_rewrite, cl.description, cl.meta_title, cl.meta_description,
                    (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'wsb_post` p
                        WHERE p.id_wsb_category = c.id_wsb_category AND p.active = 1 AND p.date_publish <= NOW()) AS nb_posts
                FROM `' . _DB_PREFIX_ . 'wsb_category` c
                JOIN `' . _DB_PREFIX_ . 'wsb_category_lang` cl ON cl.id_wsb_category = c.id_wsb_category AND cl.id_lang = ' . (int) $idLang . '
                WHERE c.active = 1
                ' . ($onlyWithPosts ? 'HAVING nb_posts > 0' : '') . '
                ORDER BY c.position ASC, cl.name ASC';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public static function getByRewrite($rewrite, $idLang, $onlyActive = true)
    {
        $sql = 'SELECT c.*, cl.name, cl.link_rewrite, cl.description, cl.meta_title, cl.meta_description
                FROM `' . _DB_PREFIX_ . 'wsb_category` c
                JOIN `' . _DB_PREFIX_ . 'wsb_category_lang` cl ON cl.id_wsb_category = c.id_wsb_category AND cl.id_lang = ' . (int) $idLang . '
                WHERE cl.link_rewrite = \'' . pSQL($rewrite) . '\'' . ($onlyActive ? ' AND c.active = 1' : '');

        return Db::getInstance()->getRow($sql) ?: null;
    }

    /**
     * Liste [id => nom] pour les formulaires d'administration.
     */
    public static function getOptions($idLang)
    {
        $rows = Db::getInstance()->executeS('SELECT c.id_wsb_category AS id, cl.name
            FROM `' . _DB_PREFIX_ . 'wsb_category` c
            JOIN `' . _DB_PREFIX_ . 'wsb_category_lang` cl ON cl.id_wsb_category = c.id_wsb_category AND cl.id_lang = ' . (int) $idLang . '
            ORDER BY c.position ASC') ?: [];

        return $rows;
    }

    public function delete()
    {
        // Les articles de la catégorie sont conservés mais rattachés à aucune catégorie : on refuse donc la suppression s'il en reste.
        $nb = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'wsb_post` WHERE id_wsb_category = ' . (int) $this->id);
        if ($nb > 0) {
            return false;
        }

        return parent::delete();
    }
}
