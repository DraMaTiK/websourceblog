<?php
/**
 * Tag (mot-clé) de blog.
 *
 * @author    Websource
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WsbTag extends ObjectModel
{
    public $id_wsb_tag;
    public $date_add;

    public $name;
    public $link_rewrite;

    public static $definition = [
        'table' => 'wsb_tag',
        'primary' => 'id_wsb_tag',
        'multilang' => true,
        'fields' => [
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'link_rewrite' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128],
        ],
    ];

    /**
     * Retourne l'id du tag portant ce nom (dans la langue), en le créant au besoin.
     */
    public static function findOrCreate($name, $idLang)
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $name));
        if ($name === '') {
            return 0;
        }
        $rewrite = Tools::str2url($name);
        if ($rewrite === '') {
            return 0;
        }
        $id = (int) Db::getInstance()->getValue('SELECT id_wsb_tag FROM `' . _DB_PREFIX_ . 'wsb_tag_lang`
            WHERE id_lang = ' . (int) $idLang . ' AND link_rewrite = \'' . pSQL($rewrite) . '\'');
        if ($id) {
            return $id;
        }

        $tag = new WsbTag();
        foreach (Language::getIDs(false) as $l) {
            $tag->name[$l] = $name;
            $tag->link_rewrite[$l] = $rewrite;
        }
        $tag->add();

        return (int) $tag->id;
    }

    public static function getByRewrite($rewrite, $idLang)
    {
        return Db::getInstance()->getRow('SELECT t.id_wsb_tag, tl.name, tl.link_rewrite
            FROM `' . _DB_PREFIX_ . 'wsb_tag` t
            JOIN `' . _DB_PREFIX_ . 'wsb_tag_lang` tl ON tl.id_wsb_tag = t.id_wsb_tag AND tl.id_lang = ' . (int) $idLang . '
            WHERE tl.link_rewrite = \'' . pSQL($rewrite) . '\'') ?: null;
    }

    /**
     * Tags utilisés par au moins un article publié, avec leur effectif.
     */
    public static function getPopular($idLang, $limit = 30)
    {
        return Db::getInstance()->executeS('SELECT t.id_wsb_tag, tl.name, tl.link_rewrite, COUNT(pt.id_wsb_post) AS nb
            FROM `' . _DB_PREFIX_ . 'wsb_tag` t
            JOIN `' . _DB_PREFIX_ . 'wsb_tag_lang` tl ON tl.id_wsb_tag = t.id_wsb_tag AND tl.id_lang = ' . (int) $idLang . '
            JOIN `' . _DB_PREFIX_ . 'wsb_post_tag` pt ON pt.id_wsb_tag = t.id_wsb_tag
            JOIN `' . _DB_PREFIX_ . 'wsb_post` p ON p.id_wsb_post = pt.id_wsb_post AND p.active = 1 AND p.date_publish <= NOW()
            GROUP BY t.id_wsb_tag
            ORDER BY nb DESC, tl.name ASC
            LIMIT ' . (int) $limit) ?: [];
    }

    public function delete()
    {
        Db::getInstance()->delete('wsb_post_tag', 'id_wsb_tag = ' . (int) $this->id);

        return parent::delete();
    }
}
