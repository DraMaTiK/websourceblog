{* Carte d'article : image (AVIF + lazyload), catégorie, titre, extrait *}
<article class="wsb-card" itemscope itemtype="https://schema.org/BlogPosting">
  <a class="wsb-card-media" href="{$post.url}" tabindex="-1" aria-hidden="true">
    {if $post.picture}{$post.picture nofilter}{else}<span class="wsb-card-noimg"></span>{/if}
  </a>
  <div class="wsb-card-body">
    <p class="wsb-card-meta">
      <a class="wsb-cat" href="{$post.category_url}">{$post.category_name}</a>
      <time datetime="{$post.date_iso}" itemprop="datePublished">{$post.date_display}</time>
    </p>
    <h2 class="wsb-card-title" itemprop="headline"><a href="{$post.url}" itemprop="url">{$post.title}</a></h2>
    <p class="wsb-card-excerpt" itemprop="description">{$post.excerpt_text}</p>
    <a class="wsb-more" href="{$post.url}">Lire l'article<span class="wsb-more-time"> · {$post.reading_time} min</span></a>
  </div>
</article>
