<?php
/**
 * Websource Blog — blog orienté SEO pour PrestaShop.
 *
 * - Catégories, articles, tags (multilingue)
 * - URLs réécrites : /blog, /blog/{categorie}, /blog/{categorie}/{article}, /blog/tag/{tag}
 * - Conversion AVIF (optionnelle) des images de couverture et des images du contenu
 * - Lazyload natif (loading="lazy") + dimensions explicites
 * - Carousel des derniers articles en page d'accueil (hook displayHome)
 * - Publication antidatée ou programmée (date de publication libre)
 * - Balisage schema.org (Blog, BlogPosting, BreadcrumbList), sitemap XML, canonical, Open Graph
 *
 * @author    Websource
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/WsbCategory.php';
require_once __DIR__ . '/classes/WsbTag.php';
require_once __DIR__ . '/classes/WsbPost.php';
require_once __DIR__ . '/classes/WsbImage.php';

class WebsourceBlog extends Module
{
    /** Valeurs par défaut de la configuration. */
    const DEFAULTS = [
        'WSB_BASE_SLUG' => 'blog',
        'WSB_PER_PAGE' => 9,
        'WSB_AVIF' => 0,
        'WSB_AVIF_QUALITY' => 60,
        'WSB_JPEG_QUALITY' => 82,
        'WSB_LAZYLOAD' => 1,
        'WSB_HOME' => 1,
        'WSB_HOME_COUNT' => 8,
        'WSB_HOME_AUTOPLAY' => 1,
        'WSB_HOME_TITLE' => 'Nos derniers articles',
        'WSB_BLOG_TITLE' => 'Blog',
        'WSB_BLOG_DESCRIPTION' => '',
    ];

    public function __construct()
    {
        $this->name = 'websourceblog';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'Websource';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.6', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Websource Blog');
        $this->description = $this->l('Blog orienté SEO : catégories, articles, tags, URLs réécrites, images AVIF, lazyload, carousel d\'accueil, publication antidatée.');
        $this->confirmUninstall = $this->l('Supprimer le blog, ses articles et ses images ?');
    }

    // ------------------------------------------------------------------
    // Installation
    // ------------------------------------------------------------------

    public function install()
    {
        if (!parent::install() || !$this->installSql() || !$this->installTabs()) {
            return false;
        }
        foreach (self::DEFAULTS as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        return $this->registerHook('moduleRoutes')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        // Images
        foreach (glob(WsbImage::uploadDir() . '*') ?: [] as $f) {
            if (is_file($f) && basename($f) !== 'index.php' && basename($f) !== '.htaccess') {
                @unlink($f);
            }
        }
        foreach (glob(_PS_MODULE_DIR_ . 'websourceblog/cache/avif/*.avif') ?: [] as $f) {
            @unlink($f);
        }
        foreach (array_keys(self::DEFAULTS) as $key) {
            Configuration::deleteByName($key);
        }

        return $this->uninstallTabs() && $this->uninstallSql() && parent::uninstall();
    }

    private function installSql()
    {
        $p = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $sql = [
            "CREATE TABLE IF NOT EXISTS `{$p}wsb_category` (
                `id_wsb_category` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                `position` INT UNSIGNED NOT NULL DEFAULT 0,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_wsb_category`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}wsb_category_lang` (
                `id_wsb_category` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                `link_rewrite` VARCHAR(128) NOT NULL,
                `description` TEXT,
                `meta_title` VARCHAR(255) DEFAULT NULL,
                `meta_description` VARCHAR(512) DEFAULT NULL,
                PRIMARY KEY (`id_wsb_category`, `id_lang`),
                KEY `link_rewrite` (`link_rewrite`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}wsb_post` (
                `id_wsb_post` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_wsb_category` INT UNSIGNED NOT NULL,
                `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                `featured` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `date_publish` DATETIME NOT NULL,
                `image` VARCHAR(128) NOT NULL DEFAULT '',
                `author_name` VARCHAR(128) DEFAULT NULL,
                `views` INT UNSIGNED NOT NULL DEFAULT 0,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_wsb_post`),
                KEY `publish` (`active`, `date_publish`),
                KEY `category` (`id_wsb_category`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}wsb_post_lang` (
                `id_wsb_post` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `link_rewrite` VARCHAR(128) NOT NULL,
                `excerpt` TEXT,
                `content` LONGTEXT,
                `meta_title` VARCHAR(255) DEFAULT NULL,
                `meta_description` VARCHAR(512) DEFAULT NULL,
                `image_alt` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id_wsb_post`, `id_lang`),
                KEY `link_rewrite` (`link_rewrite`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}wsb_tag` (
                `id_wsb_tag` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `date_add` DATETIME NOT NULL,
                PRIMARY KEY (`id_wsb_tag`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}wsb_tag_lang` (
                `id_wsb_tag` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                `link_rewrite` VARCHAR(128) NOT NULL,
                PRIMARY KEY (`id_wsb_tag`, `id_lang`),
                KEY `link_rewrite` (`link_rewrite`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$p}wsb_post_tag` (
                `id_wsb_post` INT UNSIGNED NOT NULL,
                `id_wsb_tag` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id_wsb_post`, `id_wsb_tag`),
                KEY `tag` (`id_wsb_tag`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function uninstallSql()
    {
        $p = _DB_PREFIX_;
        foreach (['wsb_post_tag', 'wsb_tag_lang', 'wsb_tag', 'wsb_post_lang', 'wsb_post', 'wsb_category_lang', 'wsb_category'] as $t) {
            Db::getInstance()->execute("DROP TABLE IF EXISTS `{$p}{$t}`");
        }

        return true;
    }

    /** Onglets d'administration : Blog > Articles / Catégories / Tags. */
    private function installTabs()
    {
        $langs = Language::getLanguages(false);
        $make = function ($class, $name, $parent, $icon = '', $visible = true) use ($langs) {
            $tab = new Tab();
            $tab->class_name = $class;
            $tab->module = $this->name;
            $tab->id_parent = (int) $parent;
            $tab->active = $visible ? 1 : 0;
            $tab->icon = $icon;
            foreach ($langs as $l) {
                $tab->name[$l['id_lang']] = $name;
            }

            return $tab->add() ? (int) $tab->id : 0;
        };
        $root = (int) Tab::getIdFromClassName('IMPROVE');
        $parent = $make('AdminWebsourceBlog', 'Blog', $root, 'article');
        if (!$parent) {
            return false;
        }
        $make('AdminWebsourceBlogPosts', 'Articles', $parent);
        $make('AdminWebsourceBlogCategories', 'Catégories', $parent);
        $make('AdminWebsourceBlogTags', 'Tags', $parent);

        return true;
    }

    private function uninstallTabs()
    {
        foreach (['AdminWebsourceBlogPosts', 'AdminWebsourceBlogCategories', 'AdminWebsourceBlogTags', 'AdminWebsourceBlog'] as $class) {
            $id = (int) Tab::getIdFromClassName($class);
            if ($id) {
                (new Tab($id))->delete();
            }
        }

        return true;
    }

    // ------------------------------------------------------------------
    // Réécriture d'URL
    // ------------------------------------------------------------------

    public static function baseSlug()
    {
        $slug = trim((string) Configuration::get('WSB_BASE_SLUG'), '/');

        return $slug !== '' ? $slug : 'blog';
    }

    public function hookModuleRoutes($params)
    {
        $b = self::baseSlug();
        $slug = ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'rewrite'];
        $catSlug = ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'category_rewrite'];
        $num = ['regexp' => '[0-9]+', 'param' => 'p'];
        $mk = function ($rule, $controller, $keywords = []) {
            return [
                'controller' => $controller,
                'rule' => $rule,
                'keywords' => $keywords,
                'params' => ['fc' => 'module', 'module' => 'websourceblog'],
            ];
        };

        // L'ordre compte : les règles les plus spécifiques d'abord.
        return [
            'module-websourceblog-sitemap' => $mk($b . '-sitemap.xml', 'sitemap'),
            'module-websourceblog-tagpage' => $mk($b . '/tag/{rewrite}/page/{p}', 'tag', ['rewrite' => $slug, 'p' => $num]),
            'module-websourceblog-tag' => $mk($b . '/tag/{rewrite}', 'tag', ['rewrite' => $slug]),
            'module-websourceblog-page' => $mk($b . '/page/{p}', 'list', ['p' => $num]),
            'module-websourceblog-categorypage' => $mk($b . '/{rewrite}/page/{p}', 'category', ['rewrite' => $slug, 'p' => $num]),
            'module-websourceblog-post' => $mk($b . '/{category_rewrite}/{rewrite}', 'post', ['category_rewrite' => $catSlug, 'rewrite' => $slug]),
            'module-websourceblog-category' => $mk($b . '/{rewrite}', 'category', ['rewrite' => $slug]),
            'module-websourceblog-list' => $mk($b, 'list'),
        ];
    }

    public function getListLink($page = 1, $idLang = null)
    {
        $link = Context::getContext()->link;

        return $page > 1
            ? $link->getModuleLink($this->name, 'list', ['p' => (int) $page], null, $idLang)
            : $link->getModuleLink($this->name, 'list', [], null, $idLang);
    }

    public function getCategoryLink($rewrite, $page = 1, $idLang = null)
    {
        $params = ['rewrite' => $rewrite];
        if ($page > 1) {
            $params['p'] = (int) $page;
        }

        return Context::getContext()->link->getModuleLink($this->name, 'category', $params, null, $idLang);
    }

    public function getTagLink($rewrite, $page = 1, $idLang = null)
    {
        $params = ['rewrite' => $rewrite];
        if ($page > 1) {
            $params['p'] = (int) $page;
        }

        return Context::getContext()->link->getModuleLink($this->name, 'tag', $params, null, $idLang);
    }

    public function getPostLink($categoryRewrite, $rewrite, $idLang = null)
    {
        return Context::getContext()->link->getModuleLink($this->name, 'post', [
            'category_rewrite' => $categoryRewrite,
            'rewrite' => $rewrite,
        ], null, $idLang);
    }

    public function getSitemapLink()
    {
        return Context::getContext()->link->getModuleLink($this->name, 'sitemap');
    }

    // ------------------------------------------------------------------
    // Préparation des articles pour les templates
    // ------------------------------------------------------------------

    /**
     * Ajoute liens, image, dates formatées, temps de lecture… à une ligne d'article.
     */
    public function presentPost(array $row, $withContent = false)
    {
        $idLang = (int) Context::getContext()->language->id;
        $row['url'] = $this->getPostLink($row['category_rewrite'], $row['link_rewrite'], $idLang);
        $row['category_url'] = $this->getCategoryLink($row['category_rewrite'], 1, $idLang);
        $row['picture'] = WsbImage::picture($row['image'], $row['image_alt'] ?: $row['title']);
        $row['image_url'] = WsbImage::url($row['image'], 1440);
        $ts = strtotime($row['date_publish']);
        $row['date_iso'] = date('c', $ts);
        $fmt = Context::getContext()->language->date_format_lite ?: 'd/m/Y';
        $row['date_display'] = date($fmt, $ts);
        $row['reading_time'] = WsbPost::readingTime($row['content']);
        $excerpt = trim(strip_tags((string) $row['excerpt']));
        if ($excerpt === '') {
            $excerpt = Tools::truncateString(trim(preg_replace('/\s+/u', ' ', strip_tags(preg_replace('/<\/(p|h[1-6]|li|div|blockquote)>|<br\s*\/?>/i', '$0 ', html_entity_decode((string) $row['content']))))), 170);
        }
        $row['excerpt_text'] = $excerpt;
        if ($withContent) {
            $row['content_html'] = WsbImage::optimizeContent((string) $row['content']);
        }
        unset($row['content']);

        return $row;
    }

    public function presentPosts(array $rows)
    {
        return array_map(function ($r) {
            $r['content'] = isset($r['content']) ? $r['content'] : '';

            return $this->presentPost($r);
        }, $rows);
    }

    // ------------------------------------------------------------------
    // Hooks front
    // ------------------------------------------------------------------

    public function hookDisplayHeader()
    {
        $controller = $this->context->controller;
        $isBlog = isset($controller->module) && $controller->module instanceof self;
        $isHome = isset($controller->php_self) && $controller->php_self === 'index';
        if (!$isBlog && !($isHome && Configuration::get('WSB_HOME'))) {
            return '';
        }
        $controller->registerStylesheet('websourceblog-front', 'modules/' . $this->name . '/views/css/front.css', ['media' => 'all', 'priority' => 190]);
        $controller->registerJavascript('websourceblog-front', 'modules/' . $this->name . '/views/js/front.js', ['position' => 'bottom', 'priority' => 190]);

        // Balises SEO complémentaires fournies par le contrôleur du blog (Open Graph, prev/next…)
        $this->context->smarty->assign('wsb_head', isset($controller->wsb_head) ? $controller->wsb_head : []);

        return $isBlog ? $this->display(__FILE__, 'views/templates/hook/head.tpl') : '';
    }

    /**
     * Carousel des derniers articles en page d'accueil.
     */
    public function hookDisplayHome()
    {
        if (!Configuration::get('WSB_HOME')) {
            return '';
        }
        $idLang = (int) $this->context->language->id;
        $rows = WsbPost::getLatest($idLang, max(2, (int) Configuration::get('WSB_HOME_COUNT')));
        if (count($rows) < 1) {
            return '';
        }
        $this->context->smarty->assign([
            'wsb_posts' => $this->presentPosts($rows),
            'wsb_home_title' => Configuration::get('WSB_HOME_TITLE'),
            'wsb_home_autoplay' => (int) Configuration::get('WSB_HOME_AUTOPLAY'),
            'wsb_list_url' => $this->getListLink(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/home-carousel.tpl');
    }

    // ------------------------------------------------------------------
    // Configuration
    // ------------------------------------------------------------------

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitWsbConfig')) {
            $slug = Tools::str2url(trim((string) Tools::getValue('WSB_BASE_SLUG')));
            Configuration::updateValue('WSB_BASE_SLUG', $slug !== '' ? $slug : 'blog');
            Configuration::updateValue('WSB_PER_PAGE', max(1, min(48, (int) Tools::getValue('WSB_PER_PAGE'))));
            Configuration::updateValue('WSB_AVIF', (int) (bool) Tools::getValue('WSB_AVIF') && WsbImage::avifSupported() ? 1 : 0);
            Configuration::updateValue('WSB_AVIF_QUALITY', max(20, min(90, (int) Tools::getValue('WSB_AVIF_QUALITY'))));
            Configuration::updateValue('WSB_JPEG_QUALITY', max(40, min(95, (int) Tools::getValue('WSB_JPEG_QUALITY'))));
            Configuration::updateValue('WSB_LAZYLOAD', (int) (bool) Tools::getValue('WSB_LAZYLOAD'));
            Configuration::updateValue('WSB_HOME', (int) (bool) Tools::getValue('WSB_HOME'));
            Configuration::updateValue('WSB_HOME_COUNT', max(2, min(24, (int) Tools::getValue('WSB_HOME_COUNT'))));
            Configuration::updateValue('WSB_HOME_AUTOPLAY', (int) (bool) Tools::getValue('WSB_HOME_AUTOPLAY'));
            Configuration::updateValue('WSB_HOME_TITLE', Tools::getValue('WSB_HOME_TITLE'));
            Configuration::updateValue('WSB_BLOG_TITLE', Tools::getValue('WSB_BLOG_TITLE'));
            Configuration::updateValue('WSB_BLOG_DESCRIPTION', Tools::getValue('WSB_BLOG_DESCRIPTION'));
            $output .= $this->displayConfirmation($this->l('Configuration enregistrée.'));
            if (Tools::getValue('WSB_AVIF') && !WsbImage::avifSupported()) {
                $output .= $this->displayWarning($this->l('Ce serveur ne sait pas créer d\'images AVIF (PHP GD ≥ 8.1 avec AVIF requis) : l\'option reste désactivée.'));
            }
        }
        if (Tools::isSubmit('submitWsbRegenerate')) {
            $n = WsbImage::regenerateAll();
            $output .= $this->displayConfirmation(sprintf($this->l('%d image(s) régénérée(s).'), $n));
        }

        return $output . $this->renderConfigForm();
    }

    private function renderConfigForm()
    {
        $switch = function ($name, $label, $desc = '') {
            return [
                'type' => 'switch', 'label' => $label, 'name' => $name, 'is_bool' => true, 'desc' => $desc,
                'values' => [
                    ['id' => $name . '_on', 'value' => 1, 'label' => $this->l('Oui')],
                    ['id' => $name . '_off', 'value' => 0, 'label' => $this->l('Non')],
                ],
            ];
        };
        $avifDesc = WsbImage::avifSupported()
            ? $this->l('Crée une version AVIF (plus légère) des images de couverture et des images du contenu. Après activation, utilisez « Régénérer les images ».')
            : $this->l('Non disponible : ce serveur n\'a pas le support AVIF (PHP GD ≥ 8.1).');

        $form = [
            'form' => [
                'legend' => ['title' => $this->l('Réglages du blog'), 'icon' => 'icon-cogs'],
                'input' => [
                    ['type' => 'text', 'label' => $this->l('Titre du blog'), 'name' => 'WSB_BLOG_TITLE'],
                    ['type' => 'textarea', 'label' => $this->l('Description (meta) de la page d\'accueil du blog'), 'name' => 'WSB_BLOG_DESCRIPTION', 'rows' => 3, 'cols' => 60],
                    ['type' => 'text', 'label' => $this->l('Préfixe des URLs'), 'name' => 'WSB_BASE_SLUG', 'desc' => $this->l('Ex. « blog » → /blog/categorie/article. Changer ce préfixe modifie toutes les URLs du blog.'), 'class' => 'fixed-width-xl'],
                    ['type' => 'text', 'label' => $this->l('Articles par page'), 'name' => 'WSB_PER_PAGE', 'class' => 'fixed-width-sm'],
                    $switch('WSB_LAZYLOAD', $this->l('Lazyload des images'), $this->l('Chargement différé (loading="lazy") avec dimensions explicites, sur la couverture et le contenu des articles.')),
                    $switch('WSB_AVIF', $this->l('Conversion AVIF automatique'), $avifDesc),
                    ['type' => 'text', 'label' => $this->l('Qualité AVIF (20-90)'), 'name' => 'WSB_AVIF_QUALITY', 'class' => 'fixed-width-sm'],
                    ['type' => 'text', 'label' => $this->l('Qualité JPEG (40-95)'), 'name' => 'WSB_JPEG_QUALITY', 'class' => 'fixed-width-sm'],
                    $switch('WSB_HOME', $this->l('Carousel d\'articles en page d\'accueil'), $this->l('Affiché via le hook displayHome. Si votre thème n\'appelle pas ce hook, ajoutez {hook h=\'displayHome\' mod=\'websourceblog\'} dans le template de la page d\'accueil.')),
                    ['type' => 'text', 'label' => $this->l('Titre du carousel'), 'name' => 'WSB_HOME_TITLE'],
                    ['type' => 'text', 'label' => $this->l('Nombre d\'articles dans le carousel'), 'name' => 'WSB_HOME_COUNT', 'class' => 'fixed-width-sm'],
                    $switch('WSB_HOME_AUTOPLAY', $this->l('Défilement automatique')),
                ],
                'submit' => ['title' => $this->l('Enregistrer'), 'name' => 'submitWsbConfig'],
                'buttons' => [
                    ['type' => 'submit', 'name' => 'submitWsbRegenerate', 'title' => $this->l('Régénérer les images'), 'icon' => 'process-icon-refresh', 'class' => 'btn btn-default pull-left'],
                ],
            ],
        ];
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->table = $this->table;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = 0;
        $helper->submit_action = 'submitWsbConfig';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = ['fields_value' => $this->getConfigValues(), 'languages' => $this->context->controller->getLanguages(), 'id_language' => $this->context->language->id];

        $info = '<div class="alert alert-info">'
            . sprintf($this->l('Sitemap du blog : %s'), '<a href="' . $this->getSitemapLink() . '" target="_blank" rel="noopener">' . $this->getSitemapLink() . '</a>')
            . '<br>' . sprintf($this->l('Blog : %s'), '<a href="' . $this->getListLink() . '" target="_blank" rel="noopener">' . $this->getListLink() . '</a>')
            . '</div>';

        return $info . $helper->generateForm([$form]);
    }

    private function getConfigValues()
    {
        $values = [];
        foreach (array_keys(self::DEFAULTS) as $key) {
            $values[$key] = Tools::getValue($key, Configuration::get($key));
        }

        return $values;
    }
}
