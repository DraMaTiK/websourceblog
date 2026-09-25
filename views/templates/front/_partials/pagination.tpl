{if $wsb_pagination.pages > 1}
  <nav class="wsb-pagination" aria-label="Pagination du blog">
    {if $wsb_pagination.prev}<a class="wsb-page wsb-page-nav" href="{$wsb_pagination.prev}" rel="prev">&lsaquo; Précédent</a>{/if}
    {foreach from=$wsb_pagination.items item=i}
      {if $i.page === null}<span class="wsb-page wsb-page-gap">…</span>
      {elseif $i.current}<span class="wsb-page is-current" aria-current="page">{$i.page}</span>
      {else}<a class="wsb-page" href="{$i.url}">{$i.page}</a>{/if}
    {/foreach}
    {if $wsb_pagination.next}<a class="wsb-page wsb-page-nav" href="{$wsb_pagination.next}" rel="next">Suivant &rsaquo;</a>{/if}
  </nav>
{/if}
