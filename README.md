# Websource Blog — module de blog orienté SEO pour PrestaShop

Blog complet pour PrestaShop 1.7.6+ / 8 / 9 : **catégories**, **articles**, **tags**, **URLs réécrites**, **images AVIF** (optionnel), **lazyload**, **carousel d'articles en page d'accueil** et **publication antidatée ou programmée**.

## Fonctionnalités

| | |
|---|---|
| **Catégories & articles** | Multilingue, extrait, contenu riche (éditeur PrestaShop), image de couverture, auteur, « à la une », compteur de vues, temps de lecture. |
| **Tags** | Saisie libre séparée par des virgules ; création automatique ; page par tag (`/blog/tag/mon-tag`). |
| **URLs réécrites** | `/blog`, `/blog/{categorie}`, `/blog/{categorie}/{article}`, `/blog/tag/{tag}`, pagination `/page/N`. Préfixe configurable. Slugs uniques générés automatiquement ; 301 vers l'URL canonique si la catégorie change. |
| **Images** | Couverture recadrée en 16/9, variantes 480/960/1440 px, `srcset`/`sizes`, `<picture>`. **Conversion AVIF automatique en option** (couverture + images du contenu, mises en cache), repli JPEG. |
| **Lazyload** | `loading="lazy"` + `decoding="async"` + dimensions explicites (pas de décalage de mise en page). L'image d'en-tête d'un article est chargée en priorité. |
| **Accueil** | Carousel des derniers articles (hook `displayHome`), défilement natif, flèches, points, défilement automatique (pause au survol, respect de `prefers-reduced-motion`). |
| **Publication** | Champ **date de publication libre** : date passée = article **antidaté** ; date future = **programmé** (visible automatiquement à l'heure dite, sans cron). Aperçu par lien à jeton pour les brouillons. |
| **SEO** | Balises `title`/`description`/canonical, `rel prev/next`, Open Graph + Twitter Card, `article:*`, JSON-LD `Blog`, `BlogPosting`, `CollectionPage`, `BreadcrumbList`, sitemap XML dédié (`/blog-sitemap.xml`, avec images), 404 propres. |

## Installation

1. Copier le dossier dans `modules/websourceblog/` (ou envoyer le ZIP depuis *Modules > Gestionnaire de modules*).
2. Installer le module. Un menu **Blog** apparaît (Articles, Catégories, Tags).
3. Créer au moins une catégorie, puis un article.
4. Réglages : *Modules > Websource Blog > Configurer*.

Si votre thème n'appelle pas le hook `displayHome` sur l'accueil, ajoutez dans `index.tpl` :

```smarty
{hook h='displayHome' mod='websourceblog'}
```

## Configuration

- **Préfixe des URLs** (défaut `blog`) — changer ce préfixe modifie toutes les URLs du blog.
- **Articles par page**, titre et meta description du blog.
- **Lazyload** on/off.
- **AVIF** on/off + qualité (le bouton *Régénérer les images* recrée toutes les variantes après un changement d'option). Nécessite PHP GD ≥ 8.1 compilé avec AVIF ; l'option se désactive sinon.
- **Carousel d'accueil** : activation, titre, nombre d'articles, défilement automatique.

## Antidater / programmer un article

Dans le formulaire d'article, le champ **Date de publication** est modifiable :

- date **passée** → l'article est publié à cette date (tri, JSON-LD `datePublished`, sitemap) ;
- date **future** → l'article reste invisible jusqu'à cette date (statut « Programmé » dans la liste).

Un article n'est visible que si **Actif = oui** et **date de publication ≤ maintenant**.

## Personnalisation du rendu

Les styles reposent sur des variables CSS surchargeables par le thème : `--wsb-accent`, `--wsb-dark`, `--wsb-gold`, `--wsb-cream`, `--wsb-ink`, `--wsb-line` (avec repli sur les variables `--ec-*` si elles existent). Les templates se surchargent dans `themes/{theme}/modules/websourceblog/views/templates/…`.

## Structure

```
websourceblog.php            module, réécriture d'URL, hooks, configuration
classes/                     WsbPost, WsbCategory, WsbTag, WsbImage, contrôleurs de base
controllers/front/           list, category, tag, post, sitemap
controllers/admin/           Articles, Catégories, Tags
views/templates/{front,hook} templates Smarty
views/css/front.css          styles
views/js/front.js            carousel
uploads/                     images (protégé par .htaccess)
```

## Désinstallation

Supprime les tables `wsb_*`, la configuration, les onglets et les images du blog.

## Licence

AFL-3.0 — © Websource. https://www.websource.fr
