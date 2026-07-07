{* Product preview - Premium Industrial *}
<div class="product_preview fn_product d-flex flex-column h-100">
    <div class="product_preview__top">
        <div class="ref-badges">
            <div class="ref-badge ref-badge--top">TOP-ПРОДАЖ</div>
            {if $product->variant->compare_price > 0}
                <div class="ref-badge ref-badge--promo">АКЦИЯ</div>
            {/if}
        </div>
        <div class="product_preview__image">
            <a class="d-flex align-items-center justify-content-center" aria-label="{$product->name|escape}" href="{url_generator route='product' url=$product->url}">
                {if $product->image->filename}
                    <img class="fn_img preview_img lazy" data-src="{$product->image->filename|resize:300:300}" src="{$rootUrl}/design/{get_theme}/images/xloading.gif" alt="{$product->name|escape}" title="{$product->name|escape}" loading="lazy"/>
                {else}
                    <div class="fn_img product_preview__no_image d-flex align-items-center justify-content-center" title="{$product->name|escape}">
                        {include file="svg.tpl" svgId="no_image_stub"}
                    </div>
                {/if}
            </a>
        </div>
        {* Wishlist (лапка) *}
        <div class="product_preview__wishlist-pos">
            {if is_array($wishlist->ids) && in_array($product->id, $wishlist->ids)}
                <a href="#" data-id="{$product->id}" class="fn_wishlist product_preview__wishlist selected" title="{$lang->product_remove_favorite}" data-result-text="{$lang->product_add_favorite}" data-language="product_remove_favorite">
                    <i class="fa fa-heart"></i>
                </a>
            {else}
                <a href="#" data-id="{$product->id}" class="fn_wishlist product_preview__wishlist" title="{$lang->product_add_favorite}" data-result-text="{$lang->product_remove_favorite}" data-language="product_add_favorite">
                    <i class="fa fa-heart-o"></i>
                </a>
            {/if}
        </div>
    </div>
    
    <div class="product_preview__body d-flex flex-column flex-grow-1">
        <div class="product_preview__meta d-flex justify-content-between align-items-center">
            {if $product->variant->stock > 0}
                <div class="ref-stock-badge">
                    <div class="ref-stock-pulse"></div>
                    <span>В НАЛИЧИИ СЕЙЧАС</span>
                </div>
            {/if}
            {if $product->variant->sku}
                <span class="product_preview__sku">КОД: {$product->variant->sku|escape}</span>
            {/if}
        </div>

        <div class="product_preview__title">
            <a class="product_preview__name" href="{url_generator route="product" url=$product->url}">
                {$product->name|escape}
            </a>
        </div>

        <div class="product_preview__footer mt-auto">
            <div class="product_preview__price-row d-flex align-items-center justify-content-between">
                <div class="price">
                    {if $product->variant->price > 0}
                        <span class="ref-price-from">ОТ</span> <span class="fn_price">{$product->variant->price|convert}</span> <span class="currency">{$currency->sign|escape}</span>
                    {else}
                        <span class="price_request">ЦЕНА ПО ЗАПРОСУ</span>
                    {/if}
                </div>
            </div>
            
            <form class="fn_variants preview_form" action="{url_generator route="cart"}">
                <input type="hidden" name="variant" value="{$product->variant->id}">
                <button class="product_preview__btn fn_is_stock {if $product->variant->stock < 1}product_preview__btn--order{/if} {if $product->variant->price == 0}ref-btn-request{/if}" type="submit">
                    {if $product->variant->price == 0}
                        ЗАПРОСИТЬ КП (PDF)
                    {elseif $product->variant->stock < 1}
                        ПОД ЗАКАЗ
                    {else}
                        БЫСТРЫЙ ЗАКАЗ
                    {/if}
                </button>
            </form>
        </div>
    </div>
</div>
