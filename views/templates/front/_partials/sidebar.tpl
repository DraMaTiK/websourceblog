<aside class="wsb-sidebar">
  {if $wsb_categories}
    <div class="wsb-widget">
      <p class="wsb-widget-title">Catégories</p>
      <ul class="wsb-cats">
        <li><a href="{$wsb_list_url}">Tous les articles</a></li>
        {foreach from=$wsb_categories item=c}
          <li{if $c.current} class="is-current"{/if}><a href="{$c.url}">{$c.name} <span>({$c.nb_posts})</span></a></li>
        {/foreach}
      </ul>
    </div>
  {/if}
  {if $wsb_tags}
    <div class="wsb-widget">
      <p class="wsb-widget-title">Mots-clés</p>
      <ul class="wsb-tags">
        {foreach from=$wsb_tags item=t}<li><a href="{$t.url}" rel="tag">#{$t.name}</a></li>{/foreach}
      </ul>
    </div>
  {/if}
</aside>
