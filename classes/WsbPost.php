<?php
/**
 * Article de blog.
 *
 * Un article est visible en front quand : active = 1 ET date_publish <= maintenant.
 * date_publish est librement modifiable : on peut antidater (date passée) ou programmer (date future).
 *
 * @author    Websource
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WsbPost extends ObjectModel
{
    public $id_wsb_post;
    public $id_wsb_category;
    public $active = 1;
    public $featured = 0;
    public $date_publish;
    public $image;
    public $author_name;
    public $views = 0;
    public $date_add;
    public $date_upd;

    public $title;
    public $link_rewrite;
    public $excerpt;
    public $content;
    public $meta_title;
    public $meta_description;
    public $image_alt;

    public static $definition = [
        'table' => 'wsb_post',
        'primary' => 'id_wsb_post',
        'multilang' => true,
        'fields' => [
            'id_wsb_category' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'featured' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_publish' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'image' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 128],
            'author_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'views' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            // Multilangue
            'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'link_rewrite' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128],
            'excerpt' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 1000],
            'content' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 4000000],
            'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 512],
            'image_alt' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        ],
    ];

    /** Condition SQL « publié » (alias p). */
    const PUBLISHED = 'p.active = 1 AND p.date_publish <= NOW()';

    private static function baseSelect($idLang)
    {
        return 'SELECT p.*, pl.title, pl.link_rewrite, pl.excerpt, pl.content, pl.meta_title, pl.meta_description, pl.image_alt,
                    cl.name AS category_name, cl.link_rewrite AS category_rewrite
                FROM `' . _DB_PREFIX_ . 'wsb_post` p
                JOIN `' . _DB_PREFIX_ . 'wsb_post_lang` pl ON pl.id_wsb_post = p.id_wsb_post AND pl.id_lang = ' . (int) $idLang . '
                JOIN `' . _DB_PREFIX_ . 'wsb_category_lang` cl ON cl.id_wsb_category = p.id_wsb_category AND cl.id_lang = ' . (int) $idLang . '
                JOIN `' . _DB_PREFIX_ . 'wsb_category` c ON c.id_wsb_category = p.id_wsb_category AND c.active = 1';
    }

    /**
     * Articles publiés (page $page, $perPage par page), filtrables par catégorie ou tag.
     */
    public static function getPublished($idLang, $page = 1, $perPage = 9, $idCategory = 0, $idTag = 0)
    {
        $sql = self::baseSelect($idLang) . ($idTag ? ' JOIN `' . _DB_PREFIX_ . 'wsb_post_tag` pt ON pt.id_wsb_post = p.id_wsb_post AND pt.id_wsb_tag = ' . (int) $idTag : '')
            . ' WHERE ' . self::PUBLISHED . ($idCategory ? ' AND p.id_wsb_category = ' . (int) $idCategory : '')
            . ' ORDER BY p.date_publish DESC, p.id_wsb_post DESC LIMIT ' . (int) (max(1, $page) - 1) * (int) $perPage . ', ' . (int) $perPage;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public static function countPublished($idCategory = 0, $idTag = 0)
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'wsb_post` p
                JOIN `' . _DB_PREFIX_ . 'wsb_category` c ON c.id_wsb_category = p.id_wsb_category AND c.active = 1'
            . ($idTag ? ' JOIN `' . _DB_PREFIX_ . 'wsb_post_tag` pt ON pt.id_wsb_post = p.id_wsb_post AND pt.id_wsb_tag = ' . (int) $idTag : '')
            . ' WHERE ' . self::PUBLISHED . ($idCategory ? ' AND p.id_wsb_category = ' . (int) $idCategory : '');

        return (int) Db::getInstance()->getValue($sql);
    }

    public static function getLatest($idLang, $limit = 8)
    {
        return self::getPublished($idLang, 1, $limit);
    }

    /**
     * Article publié par catégorie + slug (ou aperçu si $preview).
     */
    public static function getByRewrite($rewrite, $categoryRewrite, $idLang, $preview = false)
    {
        $sql = self::baseSelect($idLang) . ' WHERE pl.link_rewrite = \'' . pSQL($rewrite) . '\''
            . ' AND cl.link_rewrite = \'' . pSQL($categoryRewrite) . '\''
            . ($preview ? '' : ' AND ' . self::PUBLISHED);

        return Db::getInstance()->getRow($sql) ?: null;
    }

    /**
     * Article par slug seul (pour rediriger vers l'URL canonique si la catégorie a changé).
     */
    public static function getBySlug($rewrite, $idLang, $preview = false)
    {
        $sql = self::baseSelect($idLang) . ' WHERE pl.link_rewrite = \'' . pSQL($rewrite) . '\''
            . ($preview ? '' : ' AND ' . self::PUBLISHED);

        return Db::getInstance()->getRow($sql) ?: null;
    }

    /** Jeton d'aperçu (articles non publiés). */
    public static function previewToken($idPost)
    {
        return md5(_COOKIE_KEY_ . 'wsb-preview-' . (int) $idPost);
    }

    public static function getRelated($idPost, $idCategory, $idLang, $limit = 3)
    {
        $sql = self::baseSelect($idLang) . '
            LEFT JOIN (SELECT pt2.id_wsb_post, COUNT(*) AS common FROM `' . _DB_PREFIX_ . 'wsb_post_tag` pt2
                JOIN `' . _DB_PREFIX_ . 'wsb_post_tag` me ON me.id_wsb_tag = pt2.id_wsb_tag AND me.id_wsb_post = ' . (int) $idPost . '
                GROUP BY pt2.id_wsb_post) t ON t.id_wsb_post = p.id_wsb_post
            WHERE ' . self::PUBLISHED . ' AND p.id_wsb_post <> ' . (int) $idPost . '
            ORDER BY (p.id_wsb_category = ' . (int) $idCategory . ') DESC, COALESCE(t.common, 0) DESC, p.date_publish DESC
            LIMIT ' . (int) $limit;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Article précédent / suivant (dans l'ordre de publication).
     */
    public static function getSibling($post, $idLang, $next = true)
    {
        $sql = self::baseSelect($idLang) . ' WHERE ' . self::PUBLISHED . ' AND p.id_wsb_post <> ' . (int) $post['id_wsb_post']
            . ' AND p.date_publish ' . ($next ? '<' : '>') . ' \'' . pSQL($post['date_publish']) . '\''
            . ' ORDER BY p.date_publish ' . ($next ? 'DESC' : 'ASC');

        return Db::getInstance()->getRow($sql) ?: null;
    }

    // ------------------------------------------------------------------
    // Tags
    // ------------------------------------------------------------------

    public static function getTags($idPost, $idLang)
    {
        return Db::getInstance()->executeS('SELECT t.id_wsb_tag, tl.name, tl.link_rewrite
            FROM `' . _DB_PREFIX_ . 'wsb_post_tag` pt
            JOIN `' . _DB_PREFIX_ . 'wsb_tag` t ON t.id_wsb_tag = pt.id_wsb_tag
            JOIN `' . _DB_PREFIX_ . 'wsb_tag_lang` tl ON tl.id_wsb_tag = t.id_wsb_tag AND tl.id_lang = ' . (int) $idLang . '
            WHERE pt.id_wsb_post = ' . (int) $idPost . ' ORDER BY tl.name ASC') ?: [];
    }

    /**
     * Remplace les tags d'un article à partir d'une liste séparée par des virgules.
     */
    public static function setTagsFromString($idPost, $csv, $idLang)
    {
        Db::getInstance()->delete('wsb_post_tag', 'id_wsb_post = ' . (int) $idPost);
        $seen = [];
        foreach (explode(',', (string) $csv) as $name) {
            $idTag = WsbTag::findOrCreate($name, $idLang);
            if ($idTag && !isset($seen[$idTag])) {
                $seen[$idTag] = true;
                Db::getInstance()->insert('wsb_post_tag', ['id_wsb_post' => (int) $idPost, 'id_wsb_tag' => (int) $idTag]);
            }
        }
    }

    public static function getTagsAsString($idPost, $idLang)
    {
        return implode(', ', array_column(self::getTags($idPost, $idLang), 'name'));
    }

    public static function incrementViews($idPost)
    {
        Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'wsb_post` SET views = views + 1 WHERE id_wsb_post = ' . (int) $idPost);
    }

    /** Temps de lecture estimé (minutes) à 200 mots/min. */
    public static function readingTime($html)
    {
        $words = str_word_count(strip_tags((string) $html), 0, 'àâäçéèêëîïôöùûüÿœÀÂÄÇÉÈÊËÎÏÔÖÙÛÜŸŒ');

        return max(1, (int) ceil($words / 200));
    }

    public function delete()
    {
        $image = $this->image;
        Db::getInstance()->delete('wsb_post_tag', 'id_wsb_post = ' . (int) $this->id);
        $res = parent::delete();
        if ($res && $image) {
            WsbImage::deleteFiles($image);
        }

        return $res;
    }
}
