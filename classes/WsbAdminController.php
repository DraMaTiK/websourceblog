<?php
/**
 * Base des contrôleurs d'administration du blog.
 *
 * @author    Websource
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/../websourceblog.php';

abstract class WsbAdminController extends ModuleAdminController
{
    /**
     * Pour chaque champ multilingue, recopie la valeur de la langue par défaut dans les langues vides.
     * Génère le link_rewrite depuis $sourceField s'il est vide, et le rend unique dans $table.
     */
    protected function prepareLangPost(array $fields, $sourceField, $table, $primary)
    {
        $default = (int) Configuration::get('PS_LANG_DEFAULT');
        foreach ($fields as $f) {
            $defVal = Tools::getValue($f . '_' . $default);
            foreach (Language::getIDs(false) as $l) {
                if (Tools::getValue($f . '_' . $l) === '' || Tools::getValue($f . '_' . $l) === false) {
                    if ($defVal !== false && $defVal !== '') {
                        $_POST[$f . '_' . $l] = $defVal;
                    }
                }
            }
        }
        $id = (int) Tools::getValue($primary);
        foreach (Language::getIDs(false) as $l) {
            $rw = trim((string) Tools::getValue('link_rewrite_' . $l));
            $rw = Tools::str2url($rw !== '' ? $rw : (string) Tools::getValue($sourceField . '_' . $l));
            if ($rw === '') {
                continue;
            }
            $base = $rw;
            $i = 2;
            while ((int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . $table . '_lang`
                WHERE link_rewrite = \'' . pSQL($rw) . '\' AND id_lang = ' . (int) $l . ' AND `' . bqSQL($primary) . '` <> ' . $id) > 0) {
                $rw = $base . '-' . $i++;
            }
            $_POST['link_rewrite_' . $l] = $rw;
        }
    }
}
