<?php
/**
 * Base commune des contrôleurs front du blog : meta SEO, canonical, pagination, fil d'Ariane, 404.
 *
 * @author    Websource
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../websourceblog.php';

abstract class WsbBaseFrontController extends ModuleFrontController
{
    /** Balises <head> complémentaires (og:image, prev/next…). */
    public $wsb_head = [];

    protected $wsb_title = '';
    protected $wsb_description = '';
    protected $wsb_canonical = '';
    protected $wsb_robots = 'index';
    protected $wsb_breadcrumb = [];
    protected $wsb_404 = false;

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        if ($this->wsb_title !== '') {
            $page['meta']['title'] = $this->wsb_title;
        }
        $page['meta']['description'] = $this->wsb_description;
        $page['meta']['robots'] = $this->wsb_robots;
        if ($this->wsb_canonical !== '') {
            $page['canonical'] = $this->wsb_canonical;
        }
        $page['body_classes']['page-websourceblog'] = true;

        return $page;
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = [
            'title' => Configuration::get('WSB_BLOG_TITLE') ?: 'Blog',
            'url' => $this->module->getListLink(),
        ];
        foreach ($this->wsb_breadcrumb as $crumb) {
            $breadcrumb['links'][] = $crumb;
        }

        return $breadcrumb;
    }

    protected function getPageNumber()
    {
        return max(1, (int) Tools::getValue('p', 1));
    }

    protected function notFound()
    {
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        $this->wsb_robots = 'noindex';
        $this->wsb_404 = true;
    }

    /** Choisit le template du module, ou la page 404 du thème. */
    protected function useTemplate($name)
    {
        if ($this->wsb_404) {
            $this->setTemplate('errors/404');
        } else {
            $this->setTemplate('module:websourceblog/views/templates/front/' . $name . '.tpl');
        }
    }

    /**
     * Pagination : liens numérotés + rel prev/next.
     *
     * @param callable $linkFn function(int $page): string
     */
    protected function buildPagination($total, $page, $perPage, callable $linkFn)
    {
        $pages = (int) max(1, ceil($total / $perPage));
        $items = [];
        for ($i = 1; $i <= $pages; ++$i) {
            if ($i === 1 || $i === $pages || abs($i - $page) <= 2) {
                $items[] = ['page' => $i, 'url' => $linkFn($i), 'current' => $i === $page];
            } elseif (end($items)['page'] !== null) {
                $items[] = ['page' => null, 'url' => '', 'current' => false]; // ellipsis
            }
        }
        // supprime les ellipses consécutives
        $clean = [];
        foreach ($items as $it) {
            if ($it['page'] === null && $clean && end($clean)['page'] === null) {
                continue;
            }
            $clean[] = $it;
        }
        if ($page > 1) {
            $this->wsb_head['prev'] = $linkFn($page - 1);
        }
        if ($page < $pages) {
            $this->wsb_head['next'] = $linkFn($page + 1);
        }

        return [
            'pages' => $pages,
            'current' => $page,
            'items' => $clean,
            'prev' => $page > 1 ? $linkFn($page - 1) : '',
            'next' => $page < $pages ? $linkFn($page + 1) : '',
        ];
    }

    /** Barre latérale : catégories + tags populaires. */
    protected function assignSidebar($currentCategoryRewrite = '')
    {
        $idLang = (int) $this->context->language->id;
        $cats = WsbCategory::getActive($idLang, true);
        foreach ($cats as &$c) {
            $c['url'] = $this->module->getCategoryLink($c['link_rewrite'], 1, $idLang);
            $c['current'] = $c['link_rewrite'] === $currentCategoryRewrite;
        }
        unset($c);
        $tags = WsbTag::getPopular($idLang, 24);
        foreach ($tags as &$t) {
            $t['url'] = $this->module->getTagLink($t['link_rewrite'], 1, $idLang);
        }
        unset($t);
        $this->context->smarty->assign([
            'wsb_categories' => $cats,
            'wsb_tags' => $tags,
            'wsb_list_url' => $this->module->getListLink(),
            'wsb_blog_title' => Configuration::get('WSB_BLOG_TITLE') ?: 'Blog',
        ]);
    }

    protected function shopLogoUrl()
    {
        $logo = Configuration::get('PS_LOGO');

        return $logo ? $this->context->link->getBaseLink() . 'img/' . $logo : '';
    }

    /** JSON-LD sérialisé pour <script type="application/ld+json">. */
    protected function jsonLd(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    protected function breadcrumbLd(array $crumbs)
    {
        $items = [];
        foreach (array_values($crumbs) as $i => $c) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['title'], 'item' => $c['url']];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
