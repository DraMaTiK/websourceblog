<?php
/**
 * Page d'une catégorie (/blog/{categorie}, /blog/{categorie}/page/N).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/WsbBaseFrontController.php';

class WebsourceBlogCategoryModuleFrontController extends WsbBaseFrontController
{
    public function init()
    {
        parent::init();
        $idLang = (int) $this->context->language->id;
        $cat = WsbCategory::getByRewrite((string) Tools::getValue('rewrite'), $idLang);
        if (!$cat) {
            $this->notFound();

            return;
        }
        $per = max(1, (int) Configuration::get('WSB_PER_PAGE'));
        $page = $this->getPageNumber();
        $total = WsbPost::countPublished((int) $cat['id_wsb_category']);
        if ($page > 1 && ($page - 1) * $per >= $total) {
            $this->notFound();

            return;
        }
        $shop = Configuration::get('PS_SHOP_NAME');
        $this->wsb_title = ($cat['meta_title'] ?: $cat['name']) . ($page > 1 ? ' - Page ' . $page : '') . ' - ' . $shop;
        $this->wsb_description = $cat['meta_description'] ?: Tools::truncateString(trim(strip_tags((string) $cat['description'])), 160);
        $this->wsb_canonical = $this->module->getCategoryLink($cat['link_rewrite'], $page);
        $this->wsb_robots = 'index';
        $this->wsb_breadcrumb = [['title' => $cat['name'], 'url' => $this->module->getCategoryLink($cat['link_rewrite'])]];

        $posts = $this->module->presentPosts(WsbPost::getPublished($idLang, $page, $per, (int) $cat['id_wsb_category']));
        $pagination = $this->buildPagination($total, $page, $per, function ($p) use ($cat) {
            return $this->module->getCategoryLink($cat['link_rewrite'], $p);
        });
        $this->context->smarty->assign([
            'wsb_heading' => $cat['name'],
            'wsb_intro' => (string) $cat['description'],
            'wsb_posts' => $posts,
            'wsb_pagination' => $pagination,
            'wsb_jsonld' => $this->jsonLd([
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'CollectionPage',
                        'name' => $cat['name'],
                        'description' => $this->wsb_description,
                        'url' => $this->wsb_canonical,
                        'isPartOf' => ['@type' => 'Blog', 'url' => $this->module->getListLink()],
                    ],
                    $this->breadcrumbLd([
                        ['title' => 'Accueil', 'url' => $this->context->link->getPageLink('index')],
                        ['title' => Configuration::get('WSB_BLOG_TITLE') ?: 'Blog', 'url' => $this->module->getListLink()],
                        ['title' => $cat['name'], 'url' => $this->module->getCategoryLink($cat['link_rewrite'])],
                    ]),
                ],
            ]),
        ]);
        $this->assignSidebar($cat['link_rewrite']);
    }

    public function initContent()
    {
        parent::initContent();
        $this->useTemplate('list');
    }
}
