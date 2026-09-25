{extends file='page.tpl'}

{block name='page_title'}{$wsb_heading}{/block}

{block name='page_content_container'}
  <section id="content" class="page-content wsb-blog">
    {if $wsb_intro}<div class="wsb-intro">{$wsb_intro nofilter}</div>{/if}
    <div class="wsb-layout">
      <div class="wsb-main">
        {if $wsb_posts}
          <div class="wsb-grid">
            {foreach from=$wsb_posts item=post}{include file='module:websourceblog/views/templates/front/_partials/post-card.tpl' post=$post}{/foreach}
          </div>
          {include file='module:websourceblog/views/templates/front/_partials/pagination.tpl'}
        {else}
          <p class="wsb-empty">Aucun article pour le moment.</p>
        {/if}
      </div>
      {include file='module:websourceblog/views/templates/front/_partials/sidebar.tpl'}
    </div>
    <script type="application/ld+json">{$wsb_jsonld nofilter}</script>
  </section>
{/block}
