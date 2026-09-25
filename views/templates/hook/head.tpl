{* Balises SEO complémentaires (pages du blog) *}
{if !empty($wsb_head.prev)}<link rel="prev" href="{$wsb_head.prev}">{/if}
{if !empty($wsb_head.next)}<link rel="next" href="{$wsb_head.next}">{/if}
{if !empty($wsb_head.image)}<meta property="og:image" content="{$wsb_head.image}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{$wsb_head.image}">{/if}
{if !empty($wsb_head.og_type)}<meta property="og:type" content="{$wsb_head.og_type}">
<meta property="article:published_time" content="{$wsb_head.published}">
<meta property="article:modified_time" content="{$wsb_head.modified}">
<meta property="article:section" content="{$wsb_head.section|escape:'html':'UTF-8'}">
{foreach from=$wsb_head.tags item=tg}<meta property="article:tag" content="{$tg|escape:'html':'UTF-8'}">
{/foreach}{/if}
