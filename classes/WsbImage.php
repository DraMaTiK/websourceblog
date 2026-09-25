<?php
/**
 * Images du blog : recadrage 16/9, variantes responsives, conversion AVIF (optionnelle), lazyload.
 *
 * @author    Websource
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WsbImage
{
    /** Largeurs générées pour l'image de couverture (ratio 16/9). */
    const WIDTHS = [480, 960, 1440];
    const RATIO_W = 16;
    const RATIO_H = 9;
    const MAX_UPLOAD = 8388608; // 8 Mo

    public static function uploadDir()
    {
        return _PS_MODULE_DIR_ . 'websourceblog/uploads/';
    }

    public static function uploadUrl()
    {
        return Context::getContext()->link->getBaseLink() . 'modules/websourceblog/uploads/';
    }

    public static function cacheDir()
    {
        $dir = _PS_MODULE_DIR_ . 'websourceblog/cache/avif/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            @file_put_contents(dirname($dir) . '/index.php', "<?php\nheader('Location: ../');\nexit;\n");
            @file_put_contents($dir . 'index.php', "<?php\nheader('Location: ../');\nexit;\n");
        }

        return $dir;
    }

    public static function avifSupported()
    {
        return function_exists('imageavif') && function_exists('imagecreatefromstring');
    }

    // ------------------------------------------------------------------
    // Génération
    // ------------------------------------------------------------------

    /**
     * Recadre (cover) l'image source et crée les variantes JPG (+ AVIF si demandé).
     *
     * @return bool
     */
    public static function generateVariants($srcPath, $base, $withAvif, $jpegQuality = 82, $avifQuality = 60)
    {
        $data = @file_get_contents($srcPath);
        $src = $data ? @imagecreatefromstring($data) : false;
        if (!$src) {
            return false;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        // zone de recadrage 16/9 centrée
        $targetRatio = self::RATIO_W / self::RATIO_H;
        if ($sw / $sh > $targetRatio) {
            $cropH = $sh;
            $cropW = (int) round($sh * $targetRatio);
        } else {
            $cropW = $sw;
            $cropH = (int) round($sw / $targetRatio);
        }
        $cropX = (int) floor(($sw - $cropW) / 2);
        $cropY = (int) floor(($sh - $cropH) / 2);

        $ok = true;
        foreach (self::WIDTHS as $w) {
            if ($w > $cropW && $w !== self::WIDTHS[0]) {
                continue; // pas d'agrandissement (sauf la plus petite variante)
            }
            $h = (int) round($w * self::RATIO_H / self::RATIO_W);
            $dst = imagecreatetruecolor($w, $h);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $w, $h, $cropW, $cropH);
            $ok = imagejpeg($dst, self::uploadDir() . $base . '-' . $w . '.jpg', (int) $jpegQuality) && $ok;
            if ($withAvif && self::avifSupported()) {
                @imageavif($dst, self::uploadDir() . $base . '-' . $w . '.avif', (int) $avifQuality, 6);
            }
            imagedestroy($dst);
        }
        imagedestroy($src);

        return $ok;
    }

    /**
     * Enregistre un fichier téléversé ($_FILES[$field]) et génère les variantes.
     *
     * @return string|false nom de base de l'image, ou false
     */
    public static function handleUpload($field, $base, $withAvif, array &$errors)
    {
        if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return false;
        }
        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Échec de l\'envoi de l\'image.';

            return false;
        }
        if ($file['size'] > self::MAX_UPLOAD) {
            $errors[] = 'Image trop volumineuse (8 Mo maximum).';

            return false;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true) || !@getimagesize($file['tmp_name'])) {
            $errors[] = 'Format d\'image non valide (JPG, PNG, GIF, WEBP, AVIF).';

            return false;
        }
        if (!is_dir(self::uploadDir())) {
            @mkdir(self::uploadDir(), 0755, true);
        }
        $orig = self::uploadDir() . $base . '-orig.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $orig)) {
            $errors[] = 'Impossible d\'enregistrer l\'image.';

            return false;
        }
        @chmod($orig, 0644);
        $q = (int) Configuration::get('WSB_JPEG_QUALITY') ?: 82;
        $aq = (int) Configuration::get('WSB_AVIF_QUALITY') ?: 60;
        if (!self::generateVariants($orig, $base, $withAvif, $q, $aq)) {
            $errors[] = 'Impossible de traiter l\'image (fichier corrompu ?).';
            @unlink($orig);

            return false;
        }

        return $base;
    }

    public static function deleteFiles($base)
    {
        if (!$base || preg_match('/[^A-Za-z0-9_\-]/', $base)) {
            return;
        }
        foreach (glob(self::uploadDir() . $base . '-*') ?: [] as $f) {
            @unlink($f);
        }
    }

    /**
     * Régénère toutes les variantes depuis les originaux (après changement d'options).
     */
    public static function regenerateAll()
    {
        $avif = (bool) Configuration::get('WSB_AVIF');
        $q = (int) Configuration::get('WSB_JPEG_QUALITY') ?: 82;
        $aq = (int) Configuration::get('WSB_AVIF_QUALITY') ?: 60;
        $n = 0;
        foreach (Db::getInstance()->executeS('SELECT DISTINCT image FROM `' . _DB_PREFIX_ . 'wsb_post` WHERE image <> \'\'') ?: [] as $r) {
            $orig = glob(self::uploadDir() . $r['image'] . '-orig.*');
            if (!$orig) {
                continue;
            }
            if (!$avif) {
                foreach (glob(self::uploadDir() . $r['image'] . '-*.avif') ?: [] as $f) {
                    @unlink($f);
                }
            }
            if (self::generateVariants($orig[0], $r['image'], $avif, $q, $aq)) {
                ++$n;
            }
        }

        return $n;
    }

    // ------------------------------------------------------------------
    // Rendu
    // ------------------------------------------------------------------

    /**
     * URL de la variante (jpg) la plus proche de la largeur demandée.
     */
    public static function url($base, $width = 960, $ext = 'jpg')
    {
        if (!$base) {
            return '';
        }
        foreach (self::WIDTHS as $w) {
            if ($w >= $width && is_file(self::uploadDir() . $base . '-' . $w . '.' . $ext)) {
                return self::uploadUrl() . $base . '-' . $w . '.' . $ext;
            }
        }
        foreach (array_reverse(self::WIDTHS) as $w) {
            if (is_file(self::uploadDir() . $base . '-' . $w . '.' . $ext)) {
                return self::uploadUrl() . $base . '-' . $w . '.' . $ext;
            }
        }

        return '';
    }

    /**
     * Balise <picture> responsive : AVIF (si présent) + JPG, lazyload, dimensions explicites.
     */
    public static function picture($base, $alt, $sizes = '(min-width: 992px) 380px, 100vw', $eager = false, $class = '')
    {
        if (!$base) {
            return '';
        }
        $srcset = ['jpg' => [], 'avif' => []];
        foreach (self::WIDTHS as $w) {
            foreach (['jpg', 'avif'] as $ext) {
                if (is_file(self::uploadDir() . $base . '-' . $w . '.' . $ext)) {
                    $srcset[$ext][] = self::uploadUrl() . $base . '-' . $w . '.' . $ext . ' ' . $w . 'w';
                }
            }
        }
        if (!$srcset['jpg']) {
            return '';
        }
        $lazy = (bool) Configuration::get('WSB_LAZYLOAD') && !$eager;
        $fallback = self::url($base, 960);
        $img = '<img src="' . htmlspecialchars($fallback, ENT_QUOTES) . '" srcset="' . htmlspecialchars(implode(', ', $srcset['jpg']), ENT_QUOTES) . '"'
            . ' sizes="' . htmlspecialchars($sizes, ENT_QUOTES) . '" width="960" height="540"'
            . ' alt="' . htmlspecialchars((string) $alt, ENT_QUOTES) . '"'
            . ($class ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '')
            . ($lazy ? ' loading="lazy"' : ' loading="eager" fetchpriority="high"') . ' decoding="async">';
        $out = '<picture>';
        if ($srcset['avif'] && Configuration::get('WSB_AVIF')) {
            $out .= '<source type="image/avif" srcset="' . htmlspecialchars(implode(', ', $srcset['avif']), ENT_QUOTES) . '" sizes="' . htmlspecialchars($sizes, ENT_QUOTES) . '">';
        }

        return $out . $img . '</picture>';
    }

    // ------------------------------------------------------------------
    // Images dans le contenu des articles
    // ------------------------------------------------------------------

    /**
     * Ajoute le lazyload aux <img> du contenu et, si activé, sert une version AVIF (créée et mise en cache à la volée).
     */
    public static function optimizeContent($html)
    {
        if ($html === '' || stripos($html, '<img') === false) {
            return $html;
        }
        $lazy = (bool) Configuration::get('WSB_LAZYLOAD');
        $avif = (bool) Configuration::get('WSB_AVIF') && self::avifSupported();
        if (!$lazy && !$avif) {
            return $html;
        }
        $domain = Tools::getShopDomainSsl(false);

        return preg_replace_callback('#(<picture[^>]*>.*?</picture>)|<img\b[^>]*>#is', function ($m) use ($lazy, $avif, $domain) {
            if (!empty($m[1])) {
                return $m[0]; // déjà dans un <picture>
            }
            $tag = $m[0];
            $attrs = [];
            if (preg_match_all('/([a-zA-Z_:][\w:.-]*)\s*=\s*("([^"]*)"|\'([^\']*)\')/', $tag, $am, PREG_SET_ORDER)) {
                foreach ($am as $a) {
                    $attrs[strtolower($a[1])] = isset($a[4]) && $a[4] !== '' ? $a[4] : $a[3];
                }
            }
            $src = isset($attrs['src']) ? html_entity_decode($attrs['src']) : '';
            if ($src === '') {
                return $tag;
            }
            if ($lazy && !isset($attrs['loading'])) {
                $tag = preg_replace('/<img\b/i', '<img loading="lazy" decoding="async"', $tag, 1);
            }
            $path = parse_url($src, PHP_URL_PATH);
            $host = parse_url($src, PHP_URL_HOST);
            $isLocal = $path && (!$host || strcasecmp($host, $domain) === 0);
            if (!$isLocal) {
                return $tag;
            }
            $file = realpath(_PS_ROOT_DIR_ . '/' . ltrim(rawurldecode($path), '/'));
            $ext = $file ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : '';
            if (!$file || strpos($file, realpath(_PS_ROOT_DIR_)) !== 0 || !in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                return $tag;
            }
            if (!isset($attrs['width']) || !isset($attrs['height'])) {
                $size = @getimagesize($file);
                if ($size) {
                    $tag = preg_replace('/<img\b/i', '<img width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"', $tag, 1);
                }
            }
            if (!$avif) {
                return $tag;
            }
            $key = md5($file . '|' . filemtime($file) . '|' . (int) Configuration::get('WSB_AVIF_QUALITY'));
            $avifFile = self::cacheDir() . $key . '.avif';
            if (!is_file($avifFile)) {
                $data = @file_get_contents($file);
                $im = $data ? @imagecreatefromstring($data) : false;
                if (!$im) {
                    return $tag;
                }
                imagepalettetotruecolor($im);
                imagealphablending($im, true);
                imagesavealpha($im, true);
                $okAvif = @imageavif($im, $avifFile, (int) Configuration::get('WSB_AVIF_QUALITY') ?: 60, 6);
                imagedestroy($im);
                if (!$okAvif || !is_file($avifFile) || filesize($avifFile) === 0) {
                    @unlink($avifFile);

                    return $tag;
                }
            }
            $url = Context::getContext()->link->getBaseLink() . 'modules/websourceblog/cache/avif/' . $key . '.avif';

            return '<picture><source type="image/avif" srcset="' . $url . '">' . $tag . '</picture>';
        }, $html) ?: $html;
    }
}
