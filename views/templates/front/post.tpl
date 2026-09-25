{extends file='page.tpl'}

{block name='page_title'}{$wsb_post.title}{/block}

{block name='page_content_container'}
  <section id="content" class="page-content wsb-blog wsb-single">
    {if $wsb_preview}<p class="wsb-preview">Aperçu : cet article n'est pas (encore) publié.</p>{/if}
    <div class="wsb-layout">
      <article class="wsb-main wsb-article" itemscope itemtype="https://schema.org/BlogPosting">
        <p class="wsb-card-meta">
          <a class="wsb-cat" href="{$wsb_post.category_url}">{$wsb_post.category_name}</a>
          <time datetime="{$wsb_post.date_iso}" itemprop="datePublished">{$wsb_post.date_display}</time>
          <span>· {$wsb_post.reading_time} min de lecture</span>
          {if $wsb_post.author_name}<span>· {$wsb_post.author_name}</span>{/if}
        </p>
        {if $wsb_post.picture_hero}
          <figure class="wsb-cover">{$wsb_post.picture_hero nofilter}</figure>
        {/if}
        {if $wsb_post.excerpt_text}<p class="wsb-lead" itemprop="description">{$wsb_post.excerpt_text}</p>{/if}
        <div class="wsb-content" itemprop="articleBody">{$wsb_post.content_html nofilter}</div>

        {if $wsb_post_tags}
          <ul class="wsb-tags wsb-post-tags">
            {foreach from=$wsb_post_tags item=t}<li><a href="{$t.url}" rel="tag">#{$t.name}</a></li>{/foreach}
          </ul>
        {/if}

        <nav class="wsb-prevnext" aria-label="Articles voisins">
          {if $wsb_prev}<a class="wsb-prev" href="{$wsb_prev.url}" rel="prev"><span>Article suivant</span>{$wsb_prev.title}</a>{else}<span></span>{/if}
          {if $wsb_next}<a class="wsb-next" href="{$wsb_next.url}" rel="next"><span>Article précédent</span>{$wsb_next.title}</a>{/if}
        </nav>
      </article>
      {include file='module:websourceblog/views/templates/front/_partials/sidebar.tpl'}
    </div>

    {if $wsb_related}
      <section class="wsb-related">
        <h2 class="wsb-related-title">Dans la même thématique</h2>
        <div class="wsb-grid">
          {foreach from=$wsb_related item=post}{include file='module:websourceblog/views/templates/front/_partials/post-card.tpl' post=$post}{/foreach}
        </div>
      </section>
    {/if}
    <script type="application/ld+json">{$wsb_jsonld nofilter}</script>
  </section>
{/block}
