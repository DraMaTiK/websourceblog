{* Carousel des derniers articles (page d'accueil) *}
<section class="wsb-home" aria-label="{$wsb_home_title|escape:'html':'UTF-8'}">
  <div class="wsb-home-head">
    <h2 class="wsb-home-title">{$wsb_home_title}</h2>
    <a class="wsb-home-all" href="{$wsb_list_url}">Tous les articles</a>
  </div>
  <div class="wsb-carousel" data-wsb-carousel data-autoplay="{$wsb_home_autoplay}">
    <div class="wsb-track" tabindex="0" aria-label="Articles du blog, défilement horizontal">
      {foreach from=$wsb_posts item=post}
        <div class="wsb-slide">{include file='module:websourceblog/views/templates/front/_partials/post-card.tpl' post=$post}</div>
      {/foreach}
    </div>
  </div>
</section>
