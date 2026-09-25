<?php
/**
 * Sitemap XML du blog (/blog-sitemap.xml) : accueil du blog, catégories, tags et articles publiés.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class WebsourceBlogSitemapModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function display()
    {
        $idLang = (int) $this->context->language->id;
        $m = $this->module;
        $urls = [['loc' => $m->getListLink(), 'lastmod' => null, 'priority' => '0.8', 'freq' => 'daily']];
        foreach (WsbCategory::getActive($idLang, true) as $c) {
            $urls[] = ['loc' => $m->getCategoryLink($c['link_rewrite'], 1, $idLang), 'lastmod' => $c['date_upd'], 'priority' => '0.6', 'freq' => 'weekly'];
        }
        foreach (WsbTag::getPopular($idLang, 500) as $t) {
            $urls[] = ['loc' => $m->getTagLink($t['link_rewrite'], 1, $idLang), 'lastmod' => null, 'priority' => '0.4', 'freq' => 'weekly'];
        }
        $rows = Db::getInstance()->executeS('SELECT p.date_upd, p.date_publish, pl.link_rewrite, cl.link_rewrite AS category_rewrite, p.image
            FROM `' . _DB_PREFIX_ . 'wsb_post` p
            JOIN `' . _DB_PREFIX_ . 'wsb_post_lang` pl ON pl.id_wsb_post = p.id_wsb_post AND pl.id_lang = ' . $idLang . '
            JOIN `' . _DB_PREFIX_ . 'wsb_category` c ON c.id_wsb_category = p.id_wsb_category AND c.active = 1
            JOIN `' . _DB_PREFIX_ . 'wsb_category_lang` cl ON cl.id_wsb_category = p.id_wsb_category AND cl.id_lang = ' . $idLang . '
            WHERE ' . WsbPost::PUBLISHED . ' ORDER BY p.date_publish DESC') ?: [];
        foreach ($rows as $r) {
            $urls[] = [
                'loc' => $m->getPostLink($r['category_rewrite'], $r['link_rewrite'], $idLang),
                'lastmod' => $r['date_upd'],
                'priority' => '0.7',
                'freq' => 'monthly',
                'image' => WsbImage::url($r['image'], 1440),
            ];
        }
        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . date('c', strtotime($u['lastmod'])) . "</lastmod>\n";
            }
            $xml .= '    <changefreq>' . $u['freq'] . "</changefreq>\n    <priority>" . $u['priority'] . "</priority>\n";
            if (!empty($u['image'])) {
                $xml .= '    <image:image><image:loc>' . htmlspecialchars($u['image'], ENT_XML1) . "</image:loc></image:image>\n";
            }
            $xml .= "  </url>\n";
        }
        echo $xml . '</urlset>';
        exit;
    }
}
