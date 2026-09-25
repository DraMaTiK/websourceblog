<?php
/**
 * Page d'un tag (/blog/tag/{tag}, /blog/tag/{tag}/page/N).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/WsbBaseFrontController.php';

class WebsourceBlogTagModuleFrontController extends WsbBaseFrontController
{
    public function init()
    {
        parent::init();
        $idLang = (int) $this->context->language->id;
        $tag = WsbTag::getByRewrite((string) Tools::getValue('rewrite'), $idLang);
        if (!$tag) {
            $this->notFound();

            return;
        }
        $per = max(1, (int) Configuration::get('WSB_PER_PAGE'));
        $page = $this->getPageNumber();
        $total = WsbPost::countPublished(0, (int) $tag['id_wsb_tag']);
        if ($total === 0 || ($page > 1 && ($page - 1) * $per >= $total)) {
            $this->notFound();

            return;
        }
        $shop = Configuration::get('PS_SHOP_NAME');
        $this->wsb_title = 'Articles « ' . $tag['name'] . ' »' . ($page > 1 ? ' - Page ' . $page : '') . ' - ' . $shop;
        $this->wsb_description = 'Tous les articles du blog ' . $shop . ' sur le thème « ' . $tag['name'] . ' ».';
        $this->wsb_canonical = $this->module->getTagLink($tag['link_rewrite'], $page);
        $this->wsb_robots = 'index';
        $this->wsb_breadcrumb = [['title' => '#' . $tag['name'], 'url' => $this->module->getTagLink($tag['link_rewrite'])]];

        $posts = $this->module->presentPosts(WsbPost::getPublished($idLang, $page, $per, 0, (int) $tag['id_wsb_tag']));
        $pagination = $this->buildPagination($total, $page, $per, function ($p) use ($tag) {
            return $this->module->getTagLink($tag['link_rewrite'], $p);
        });
        $this->context->smarty->assign([
            'wsb_heading' => '#' . $tag['name'],
            'wsb_intro' => '',
            'wsb_posts' => $posts,
            'wsb_pagination' => $pagination,
            'wsb_jsonld' => $this->jsonLd([
                '@context' => 'https://schema.org',
                '@graph' => [
                    ['@type' => 'CollectionPage', 'name' => $tag['name'], 'url' => $this->wsb_canonical, 'isPartOf' => ['@type' => 'Blog', 'url' => $this->module->getListLink()]],
                    $this->breadcrumbLd([
                        ['title' => 'Accueil', 'url' => $this->context->link->getPageLink('index')],
                        ['title' => Configuration::get('WSB_BLOG_TITLE') ?: 'Blog', 'url' => $this->module->getListLink()],
                        ['title' => '#' . $tag['name'], 'url' => $this->wsb_canonical],
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
