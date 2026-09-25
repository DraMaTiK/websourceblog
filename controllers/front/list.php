<?php
/**
 * Page d'accueil du blog (/blog, /blog/page/N).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/WsbBaseFrontController.php';

class WebsourceBlogListModuleFrontController extends WsbBaseFrontController
{
    public function init()
    {
        parent::init();
        $idLang = (int) $this->context->language->id;
        $per = max(1, (int) Configuration::get('WSB_PER_PAGE'));
        $page = $this->getPageNumber();
        $total = WsbPost::countPublished();
        if ($page > 1 && ($page - 1) * $per >= $total) {
            $this->notFound();

            return;
        }
        $title = Configuration::get('WSB_BLOG_TITLE') ?: 'Blog';
        $this->wsb_title = $title . ($page > 1 ? ' - Page ' . $page : '') . ' - ' . Configuration::get('PS_SHOP_NAME');
        $this->wsb_description = (string) Configuration::get('WSB_BLOG_DESCRIPTION');
        $this->wsb_canonical = $this->module->getListLink($page);
        $this->wsb_robots = 'index';

        $posts = $this->module->presentPosts(WsbPost::getPublished($idLang, $page, $per));
        $pagination = $this->buildPagination($total, $page, $per, function ($p) {
            return $this->module->getListLink($p);
        });
        if ($posts && $posts[0]['image_url'] && $page === 1) {
            $this->wsb_head['image'] = $posts[0]['image_url'];
        }
        $this->context->smarty->assign([
            'wsb_heading' => $title,
            'wsb_intro' => (string) Configuration::get('WSB_BLOG_DESCRIPTION'),
            'wsb_posts' => $posts,
            'wsb_pagination' => $pagination,
            'wsb_jsonld' => $this->jsonLd([
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'Blog',
                        '@id' => $this->module->getListLink() . '#blog',
                        'name' => $title,
                        'description' => $this->wsb_description,
                        'url' => $this->module->getListLink(),
                        'inLanguage' => $this->context->language->language_code,
                        'publisher' => ['@type' => 'Organization', 'name' => Configuration::get('PS_SHOP_NAME'), 'url' => $this->context->link->getPageLink('index')],
                    ],
                    $this->breadcrumbLd([
                        ['title' => 'Accueil', 'url' => $this->context->link->getPageLink('index')],
                        ['title' => $title, 'url' => $this->module->getListLink()],
                    ]),
                ],
            ]),
        ]);
        $this->assignSidebar();
    }

    public function initContent()
    {
        parent::initContent();
        $this->useTemplate('list');
    }
}
