<?php
/**
 * Page d'un article (/blog/{categorie}/{article}).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../../classes/WsbBaseFrontController.php';

class WebsourceBlogPostModuleFrontController extends WsbBaseFrontController
{
    public function init()
    {
        parent::init();
        $idLang = (int) $this->context->language->id;
        $rewrite = (string) Tools::getValue('rewrite');
        $catRewrite = (string) Tools::getValue('category_rewrite');

        // Aperçu d'un article non publié : jeton propre à l'article.
        $preview = false;
        $token = (string) Tools::getValue('preview');
        $probe = WsbPost::getBySlug($rewrite, $idLang, true);
        if ($probe && $token !== '' && hash_equals(WsbPost::previewToken($probe['id_wsb_post']), $token)) {
            $preview = true;
        }
        $row = WsbPost::getByRewrite($rewrite, $catRewrite, $idLang, $preview);
        if (!$row) {
            // Article existant mais catégorie différente : redirection 301 vers l'URL canonique.
            $other = WsbPost::getBySlug($rewrite, $idLang, $preview);
            if ($other) {
                Tools::redirect($this->module->getPostLink($other['category_rewrite'], $other['link_rewrite'], $idLang), __PS_BASE_URI__, null, 'HTTP/1.1 301 Moved Permanently');
            }
            $this->notFound();

            return;
        }
        if (!$preview) {
            WsbPost::incrementViews((int) $row['id_wsb_post']);
        }
        $post = $this->module->presentPost($row, true);
        $post['picture_hero'] = WsbImage::picture($post['image'], $post['image_alt'] ?: $post['title'], '(min-width: 992px) 760px, 100vw', true, 'wsb-hero-img');
        if (strpos((string) $post['content_html'], 'wsb-video') !== false) {
            $post['picture_hero'] = ''; // la vidéo en tête d'article remplace l'image de couverture
        }
        $tags = WsbPost::getTags((int) $post['id_wsb_post'], $idLang);
        foreach ($tags as &$t) {
            $t['url'] = $this->module->getTagLink($t['link_rewrite'], 1, $idLang);
        }
        unset($t);
        $related = $this->module->presentPosts(WsbPost::getRelated((int) $post['id_wsb_post'], (int) $post['id_wsb_category'], $idLang, 3));
        $prev = WsbPost::getSibling($row, $idLang, false);
        $next = WsbPost::getSibling($row, $idLang, true);

        $shop = Configuration::get('PS_SHOP_NAME');
        $this->wsb_title = ($post['meta_title'] ?: $post['title']) . ' - ' . $shop;
        $this->wsb_description = $post['meta_description'] ?: $post['excerpt_text'];
        $this->wsb_canonical = $post['url'];
        $this->wsb_robots = $preview ? 'noindex' : 'index';
        $this->wsb_breadcrumb = [
            ['title' => $post['category_name'], 'url' => $post['category_url']],
            ['title' => $post['title'], 'url' => $post['url']],
        ];
        if ($post['image_url']) {
            $this->wsb_head['image'] = $post['image_url'];
        }
        $this->wsb_head['og_type'] = 'article';
        $this->wsb_head['published'] = $post['date_iso'];
        $this->wsb_head['modified'] = date('c', strtotime($row['date_upd']));
        $this->wsb_head['section'] = $post['category_name'];
        $this->wsb_head['tags'] = array_column($tags, 'name');

        $author = $post['author_name'] ?: $shop;
        $ld = [
            '@type' => 'BlogPosting',
            '@id' => $post['url'] . '#article',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $post['url']],
            'headline' => Tools::truncateString($post['title'], 110),
            'description' => $this->wsb_description,
            'datePublished' => $post['date_iso'],
            'dateModified' => $this->wsb_head['modified'],
            'articleSection' => $post['category_name'],
            'wordCount' => str_word_count(strip_tags($post['content_html'])),
            'inLanguage' => $this->context->language->language_code,
            'author' => ['@type' => 'Person', 'name' => $author],
            'publisher' => ['@type' => 'Organization', 'name' => $shop, 'url' => $this->context->link->getPageLink('index')],
        ];
        if ($logo = $this->shopLogoUrl()) {
            $ld['publisher']['logo'] = ['@type' => 'ImageObject', 'url' => $logo];
        }
        if ($post['image_url']) {
            $ld['image'] = [WsbImage::url($post['image'], 1440)];
        }
        if ($tags) {
            $ld['keywords'] = implode(', ', array_column($tags, 'name'));
        }

        $this->context->smarty->assign([
            'wsb_post' => $post,
            'wsb_post_tags' => $tags,
            'wsb_related' => $related,
            'wsb_prev' => $prev ? $this->module->presentPost(array_merge($prev, ['content' => ''])) : null,
            'wsb_next' => $next ? $this->module->presentPost(array_merge($next, ['content' => ''])) : null,
            'wsb_preview' => $preview,
            'wsb_jsonld' => $this->jsonLd([
                '@context' => 'https://schema.org',
                '@graph' => [
                    $ld,
                    $this->breadcrumbLd([
                        ['title' => 'Accueil', 'url' => $this->context->link->getPageLink('index')],
                        ['title' => Configuration::get('WSB_BLOG_TITLE') ?: 'Blog', 'url' => $this->module->getListLink()],
                        ['title' => $post['category_name'], 'url' => $post['category_url']],
                        ['title' => $post['title'], 'url' => $post['url']],
                    ]),
                ],
            ]),
        ]);
        $this->assignSidebar($post['category_rewrite']);
    }

    public function initContent()
    {
        parent::initContent();
        $this->useTemplate('post');
    }
}
