<?php
/**
 * Product Variations Template
 * 
 * Template for product variation selection modal
 */

if (!defined('ABSPATH')) {
    exit;
}

$product_id = intval($_POST['product_id']);
$product = wc_get_product($product_id);

if (!$product || !$product->is_type('variable')) {
    return;
}

$variations = $product->get_available_variations();
$attributes = $product->get_variation_attributes();
?>

<div class="reel-product-variations">
    <div class="reel-variation-product-info">
        <div class="reel-variation-image">
            <?php echo $product->get_image('medium'); ?>
        </div>
        
        <div class="reel-variation-details">
            <h3><?php echo esc_html($product->get_name()); ?></h3>
            <div class="reel-variation-price" id="reel-variation-price">
                <?php echo $product->get_price_html(); ?>
            </div>
            
            <?php if ($product->get_short_description()): ?>
            <div class="reel-variation-description">
                <?php echo wp_kses_post($product->get_short_description()); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <form class="reel-variations-form" data-product-id="<?php echo esc_attr($product_id); ?>">
        <div class="reel-variation-attributes">
            <?php foreach ($attributes as $attribute_name => $options): ?>
                <?php
                $attribute_label = wc_attribute_label($attribute_name);
                $attribute_key = 'attribute_' . sanitize_title($attribute_name);
                ?>
                <div class="reel-variation-attribute">
                    <label for="<?php echo esc_attr($attribute_key); ?>">
                        <?php echo esc_html($attribute_label); ?>:
                    </label>
                    
                    <select 
                        id="<?php echo esc_attr($attribute_key); ?>"
                        name="<?php echo esc_attr($attribute_key); ?>"
                        data-attribute_name="<?php echo esc_attr($attribute_key); ?>"
                        class="reel-variation-select"
                    >
                        <option value=""><?php echo esc_html(sprintf(__('Escolher %s', 'reel-marketplace'), $attribute_label)); ?></option>
                        <?php foreach ($options as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>">
                                <?php echo esc_html($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="reel-variation-quantity">
            <label for="reel-variation-qty"><?php _e('Quantidade:', 'reel-marketplace'); ?></label>
            <div class="reel-quantity-input">
                <button type="button" class="reel-qty-btn reel-qty-minus" disabled>-</button>
                <input 
                    type="number" 
                    id="reel-variation-qty" 
                    name="quantity" 
                    value="1" 
                    min="1" 
                    max="<?php echo $product->get_stock_quantity() ?: 999; ?>"
                    class="reel-qty-input"
                />
                <button type="button" class="reel-qty-btn reel-qty-plus">+</button>
            </div>
        </div>
        
        <div class="reel-variation-stock-info" id="reel-variation-stock"></div>
        
        <div class="reel-variation-actions">
            <button 
                type="button" 
                class="reel-btn reel-btn-primary reel-add-to-cart-variation" 
                disabled
                data-product-id="<?php echo esc_attr($product_id); ?>"
            >
                <span class="material-icons">shopping_cart</span>
                <?php _e('Adicionar ao Carrinho', 'reel-marketplace'); ?>
            </button>
            
            <button 
                type="button" 
                class="reel-btn reel-btn-secondary reel-buy-now-variation" 
                disabled
                data-product-id="<?php echo esc_attr($product_id); ?>"
            >
                <span class="material-icons">flash_on</span>
                <?php _e('Comprar Agora', 'reel-marketplace'); ?>
            </button>
        </div>
    </form>
</div>

<!-- Variations data for JavaScript -->
<script type="application/json" id="reel-variations-data">
<?php echo json_encode($variations); ?>
</script>

<style>
.reel-product-variations {
    max-width: 500px;
}

.reel-variation-product-info {
    display: flex;
    gap: var(--reel-spacing-md);
    margin-bottom: var(--reel-spacing-lg);
    padding-bottom: var(--reel-spacing-lg);
    border-bottom: 1px solid var(--reel-outline-variant);
}

.reel-variation-image {
    flex: 0 0 100px;
}

.reel-variation-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: var(--reel-radius-sm);
}

.reel-variation-details {
    flex: 1;
}

.reel-variation-details h3 {
    margin: 0 0 var(--reel-spacing-sm) 0;
    font-size: 18px;
    color: var(--reel-on-surface);
}

.reel-variation-price {
    font-size: 20px;
    font-weight: 700;
    color: var(--reel-primary);
    margin-bottom: var(--reel-spacing-sm);
}

.reel-variation-description {
    font-size: 14px;
    color: var(--reel-on-surface-variant);
    line-height: 1.4;
}

.reel-variation-attributes {
    margin-bottom: var(--reel-spacing-lg);
}

.reel-variation-attribute {
    margin-bottom: var(--reel-spacing-md);
}

.reel-variation-attribute label {
    display: block;
    margin-bottom: var(--reel-spacing-xs);
    font-weight: 600;
    color: var(--reel-on-surface);
}

.reel-variation-select {
    width: 100%;
    padding: var(--reel-spacing-sm) var(--reel-spacing-md);
    border: 1px solid var(--reel-outline);
    border-radius: var(--reel-radius-sm);
    background: var(--reel-surface);
    color: var(--reel-on-surface);
    font-size: 14px;
}

.reel-variation-select:focus {
    outline: none;
    border-color: var(--reel-primary);
    box-shadow: 0 0 0 2px rgba(103, 80, 164, 0.2);
}

.reel-variation-quantity {
    margin-bottom: var(--reel-spacing-lg);
}

.reel-variation-quantity label {
    display: block;
    margin-bottom: var(--reel-spacing-xs);
    font-weight: 600;
    color: var(--reel-on-surface);
}

.reel-quantity-input {
    display: flex;
    align-items: center;
    width: fit-content;
    border: 1px solid var(--reel-outline);
    border-radius: var(--reel-radius-sm);
    overflow: hidden;
    background: var(--reel-surface);
}

.reel-qty-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: var(--reel-surface-variant);
    color: var(--reel-on-surface);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: background-color var(--reel-transition-fast);
}

.reel-qty-btn:hover:not(:disabled) {
    background: var(--reel-primary-container);
    color: var(--reel-on-primary-container);
}

.reel-qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.reel-qty-input {
    width: 60px;
    height: 40px;
    border: none;
    text-align: center;
    font-size: 14px;
    color: var(--reel-on-surface);
    background: transparent;
}

.reel-qty-input:focus {
    outline: none;
}

.reel-variation-stock-info {
    margin-bottom: var(--reel-spacing-lg);
    padding: var(--reel-spacing-sm);
    border-radius: var(--reel-radius-sm);
    font-size: 14px;
    text-align: center;
}

.reel-variation-stock-info.in-stock {
    background: var(--reel-tertiary-container);
    color: var(--reel-on-tertiary-container);
}

.reel-variation-stock-info.out-of-stock {
    background: var(--reel-error-container);
    color: var(--reel-on-error-container);
}

.reel-variation-actions {
    display: flex;
    gap: var(--reel-spacing-sm);
}

.reel-variation-actions .reel-btn {
    flex: 1;
}

/* Mobile responsiveness */
@media (max-width: 480px) {
    .reel-variation-product-info {
        flex-direction: column;
        text-align: center;
    }
    
    .reel-variation-image {
        flex: none;
        align-self: center;
    }
    
    .reel-variation-actions {
        flex-direction: column;
    }
}
</style>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        const variationsData = JSON.parse($('#reel-variations-data').text());
        const $form = $('.reel-variations-form');
        const $selects = $('.reel-variation-select');
        const $addToCartBtn = $('.reel-add-to-cart-variation');
        const $buyNowBtn = $('.reel-buy-now-variation');
        const $priceDisplay = $('#reel-variation-price');
        const $stockInfo = $('#reel-variation-stock');
        const $qtyInput = $('.reel-qty-input');
        const $qtyMinus = $('.reel-qty-minus');
        const $qtyPlus = $('.reel-qty-plus');
        
        let selectedVariation = null;
        
        // Handle attribute selection
        $selects.on('change', function() {
            updateVariation();
        });
        
        // Handle quantity changes
        $qtyMinus.on('click', function() {
            const currentQty = parseInt($qtyInput.val());
            if (currentQty > 1) {
                $qtyInput.val(currentQty - 1);
                updateQuantityButtons();
            }
        });
        
        $qtyPlus.on('click', function() {
            const currentQty = parseInt($qtyInput.val());
            const maxQty = parseInt($qtyInput.attr('max'));
            if (currentQty < maxQty) {
                $qtyInput.val(currentQty + 1);
                updateQuantityButtons();
            }
        });
        
        $qtyInput.on('input', function() {
            updateQuantityButtons();
        });
        
        // Handle add to cart
        $addToCartBtn.on('click', function() {
            if (selectedVariation) {
                addToCart();
            }
        });
        
        // Handle buy now
        $buyNowBtn.on('click', function() {
            if (selectedVariation) {
                buyNow();
            }
        });
        
        function updateVariation() {
            const selectedAttributes = {};
            let allSelected = true;
            
            $selects.each(function() {
                const $select = $(this);
                const attributeName = $select.data('attribute_name');
                const value = $select.val();
                
                if (value) {
                    selectedAttributes[attributeName] = value;
                } else {
                    allSelected = false;
                }
            });
            
            if (allSelected) {
                // Find matching variation
                selectedVariation = variationsData.find(function(variation) {
                    const attributes = variation.attributes;
                    return Object.keys(selectedAttributes).every(function(key) {
                        return attributes[key] === selectedAttributes[key] || attributes[key] === '';
                    });
                });
                
                if (selectedVariation) {
                    updateUI();
                } else {
                    resetUI();
                }
            } else {
                selectedVariation = null;
                resetUI();
            }
        }
        
        function updateUI() {
            if (!selectedVariation) return;
            
            // Update price
            $priceDisplay.html(selectedVariation.price_html);
            
            // Update stock info
            if (selectedVariation.is_in_stock) {
                $stockInfo.html('<span class="material-icons">check_circle</span> Em estoque')
                          .removeClass('out-of-stock')
                          .addClass('in-stock');
                
                // Update max quantity
                if (selectedVariation.max_qty && selectedVariation.max_qty > 0) {
                    $qtyInput.attr('max', selectedVariation.max_qty);
                }
                
                $addToCartBtn.prop('disabled', false);
                $buyNowBtn.prop('disabled', false);
            } else {
                $stockInfo.html('<span class="material-icons">cancel</span> Fora de estoque')
                          .removeClass('in-stock')
                          .addClass('out-of-stock');
                
                $addToCartBtn.prop('disabled', true);
                $buyNowBtn.prop('disabled', true);
            }
            
            // Update product image if variation has one
            if (selectedVariation.image && selectedVariation.image.src) {
                $('.reel-variation-image img').attr('src', selectedVariation.image.src);
            }
        }
        
        function resetUI() {
            $stockInfo.empty().removeClass('in-stock out-of-stock');
            $addToCartBtn.prop('disabled', true);
            $buyNowBtn.prop('disabled', true);
        }
        
        function updateQuantityButtons() {
            const currentQty = parseInt($qtyInput.val());
            const maxQty = parseInt($qtyInput.attr('max'));
            
            $qtyMinus.prop('disabled', currentQty <= 1);
            $qtyPlus.prop('disabled', currentQty >= maxQty);
        }
        
        function getFormData() {
            const formData = {
                product_id: $form.data('product-id'),
                variation_id: selectedVariation.variation_id,
                quantity: parseInt($qtyInput.val()),
                variation_data: {},
                nonce: reelAjax.nonce
            };
            
            $selects.each(function() {
                const $select = $(this);
                const name = $select.attr('name');
                const value = $select.val();
                if (value) {
                    formData.variation_data[name] = value;
                }
            });
            
            return formData;
        }
        
        function addToCart() {
            const formData = getFormData();
            formData.action = 'reel_add_to_cart';
            
            $addToCartBtn.prop('disabled', true).html('<span class="reel-loading-spinner"></span> Adicionando...');
            
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $('.reel-modal').removeClass('show');
                        
                        // Show success message
                        if (window.reelPlayer) {
                            window.reelPlayer.showToast(response.data.message, 'success');
                        }
                        
                        // Update cart count if available
                        if (response.data.cart_count) {
                            $('.cart-contents-count, .reel-cart-count').text(response.data.cart_count);
                        }
                    } else {
                        if (window.reelPlayer) {
                            window.reelPlayer.showToast(response.data.message || 'Erro ao adicionar ao carrinho', 'error');
                        }
                    }
                },
                error: function() {
                    if (window.reelPlayer) {
                        window.reelPlayer.showToast('Erro ao adicionar ao carrinho', 'error');
                    }
                },
                complete: function() {
                    $addToCartBtn.prop('disabled', false).html('<span class="material-icons">shopping_cart</span> Adicionar ao Carrinho');
                }
            });
        }
        
        function buyNow() {
            addToCart();
            // After adding to cart, redirect to checkout
            setTimeout(function() {
                window.location.href = reelAjax.checkoutUrl || '/checkout/';
            }, 1000);
        }
        
        // Initialize
        updateQuantityButtons();
    });
})(jQuery);
</script>
