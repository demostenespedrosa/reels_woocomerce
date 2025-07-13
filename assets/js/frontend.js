/**
 * Reel Marketplace Frontend JavaScript
 * 
 * Handles all frontend interactions for the reel feed
 */

(function($) {
    'use strict';

    class ReelPlayer {
        constructor() {
            this.currentIndex = 0;
            this.reels = [];
            this.isLoading = false;
            this.currentPage = 1;
            this.hasMore = true;
            this.touchStartY = 0;
            this.touchEndY = 0;
            this.swipeThreshold = 50;
            this.viewportHeight = window.innerHeight;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupIntersectionObserver();
            this.loadReels();
            this.setupKeyboardNavigation();
            this.setupTouchNavigation();
        }

        bindEvents() {
            // Navigation buttons
            $(document).on('click', '.reel-nav-up', () => this.previousReel());
            $(document).on('click', '.reel-nav-down', () => this.nextReel());
            
            // Reel actions
            $(document).on('click', '.reel-like', (e) => this.handleLike(e));
            $(document).on('click', '.reel-share', (e) => this.handleShare(e));
            $(document).on('click', '.reel-products', (e) => this.showProductsModal(e));
            
            // Product actions
            $(document).on('click', '.reel-add-to-cart', (e) => this.handleAddToCart(e));
            $(document).on('click', '.reel-add-to-wishlist', (e) => this.handleAddToWishlist(e));
            
            // Product indicators
            $(document).on('click', '.reel-product-indicator', (e) => this.switchProduct(e));
            
            // Modal events
            $(document).on('click', '.reel-modal-close', () => this.closeModal());
            $(document).on('click', '.reel-modal', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });
            
            // Share events
            $(document).on('click', '.reel-share-option', (e) => this.handleSharePlatform(e));
            
            // Window events
            $(window).on('resize', () => this.handleResize());
            $(window).on('beforeunload', () => this.cleanup());
        }

        setupIntersectionObserver() {
            if (!('IntersectionObserver' in window)) {
                return;
            }

            const options = {
                root: null,
                rootMargin: '0px',
                threshold: 0.5
            };

            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const reelItem = entry.target;
                    const video = reelItem.querySelector('.reel-video');
                    
                    if (entry.isIntersecting) {
                        this.playVideo(video);
                        this.trackView(reelItem.dataset.reelId);
                        this.preloadNextVideos();
                    } else {
                        this.pauseVideo(video);
                    }
                });
            }, options);

            // Observe existing reels
            $('.reel-item').each((index, element) => {
                this.observer.observe(element);
            });
        }

        setupKeyboardNavigation() {
            $(document).on('keydown', (e) => {
                if ($('.reel-modal.show').length > 0) {
                    if (e.key === 'Escape') {
                        this.closeModal();
                    }
                    return;
                }

                switch (e.key) {
                    case 'ArrowUp':
                    case 'k':
                        e.preventDefault();
                        this.previousReel();
                        break;
                    case 'ArrowDown':
                    case 'j':
                        e.preventDefault();
                        this.nextReel();
                        break;
                    case ' ':
                        e.preventDefault();
                        this.toggleCurrentVideo();
                        break;
                    case 'l':
                        e.preventDefault();
                        this.likeCurrentReel();
                        break;
                    case 's':
                        e.preventDefault();
                        this.shareCurrentReel();
                        break;
                    case 'Escape':
                        this.closeModal();
                        break;
                }
            });
        }

        setupTouchNavigation() {
            const feedContainer = $('.reel-feed')[0];
            if (!feedContainer) return;

            let isScrolling = false;
            let scrollTimeout;

            feedContainer.addEventListener('touchstart', (e) => {
                this.touchStartY = e.touches[0].clientY;
            }, { passive: true });

            feedContainer.addEventListener('touchmove', (e) => {
                isScrolling = true;
                clearTimeout(scrollTimeout);
                
                scrollTimeout = setTimeout(() => {
                    isScrolling = false;
                }, 150);
            }, { passive: true });

            feedContainer.addEventListener('touchend', (e) => {
                if (isScrolling) return;
                
                this.touchEndY = e.changedTouches[0].clientY;
                this.handleSwipe();
            }, { passive: true });

            // Handle scroll end for snap navigation
            feedContainer.addEventListener('scroll', () => {
                clearTimeout(this.scrollTimeout);
                this.scrollTimeout = setTimeout(() => {
                    this.snapToNearestReel();
                }, 150);
            }, { passive: true });
        }

        handleSwipe() {
            const swipeDistance = this.touchStartY - this.touchEndY;
            
            if (Math.abs(swipeDistance) > this.swipeThreshold) {
                if (swipeDistance > 0) {
                    // Swipe up - next reel
                    this.nextReel();
                } else {
                    // Swipe down - previous reel
                    this.previousReel();
                }
            }
        }

        snapToNearestReel() {
            const feedContainer = $('.reel-feed')[0];
            const scrollTop = feedContainer.scrollTop;
            const reelHeight = this.viewportHeight;
            const nearestIndex = Math.round(scrollTop / reelHeight);
            
            this.currentIndex = Math.max(0, Math.min(nearestIndex, this.getTotalReels() - 1));
            this.scrollToReel(this.currentIndex, 'smooth');
        }

        nextReel() {
            if (this.currentIndex < this.getTotalReels() - 1) {
                this.currentIndex++;
                this.scrollToReel(this.currentIndex);
            } else if (this.hasMore && !this.isLoading) {
                this.loadMoreReels();
            }
        }

        previousReel() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.scrollToReel(this.currentIndex);
            }
        }

        scrollToReel(index, behavior = 'smooth') {
            const feedContainer = $('.reel-feed')[0];
            const targetScrollTop = index * this.viewportHeight;
            
            if (behavior === 'smooth' && 'scrollTo' in feedContainer) {
                feedContainer.scrollTo({
                    top: targetScrollTop,
                    behavior: 'smooth'
                });
            } else {
                feedContainer.scrollTop = targetScrollTop;
            }
        }

        getTotalReels() {
            return $('.reel-item').length;
        }

        playVideo(video) {
            if (!video) return;
            
            if (video.tagName === 'VIDEO') {
                video.play().catch(e => {
                    console.log('Video autoplay prevented:', e);
                });
            }
        }

        pauseVideo(video) {
            if (!video) return;
            
            if (video.tagName === 'VIDEO') {
                video.pause();
            }
        }

        toggleCurrentVideo() {
            const currentReel = $('.reel-item').eq(this.currentIndex);
            const video = currentReel.find('.reel-video')[0];
            
            if (!video || video.tagName !== 'VIDEO') return;
            
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        }

        preloadNextVideos() {
            const preloadCount = 2;
            for (let i = 1; i <= preloadCount; i++) {
                const nextIndex = this.currentIndex + i;
                if (nextIndex < this.getTotalReels()) {
                    const nextReel = $('.reel-item').eq(nextIndex);
                    const video = nextReel.find('.reel-video')[0];
                    
                    if (video && video.tagName === 'VIDEO') {
                        video.load();
                    }
                }
            }
        }

        loadReels() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading();
            
            // Initial load is handled by the shortcode
            setTimeout(() => {
                this.isLoading = false;
                this.hideLoading();
                
                // Setup observer for new reels
                $('.reel-item').each((index, element) => {
                    if (this.observer) {
                        this.observer.observe(element);
                    }
                });
            }, 1000);
        }

        loadMoreReels() {
            if (this.isLoading || !this.hasMore) return;
            
            this.isLoading = true;
            this.showLoading();
            
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reel_load_more',
                    page: this.currentPage + 1,
                    per_page: 10,
                    nonce: reelAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const $newReels = $(response.data.html);
                        $('.reel-feed').append($newReels);
                        
                        // Setup observer for new reels
                        $newReels.each((index, element) => {
                            if (this.observer) {
                                this.observer.observe(element);
                            }
                        });
                        
                        this.currentPage++;
                        this.hasMore = response.data.has_more;
                    } else {
                        this.showToast(response.data.message || reelAjax.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showToast(reelAjax.strings.error, 'error');
                },
                complete: () => {
                    this.isLoading = false;
                    this.hideLoading();
                }
            });
        }

        handleLike(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = $(e.currentTarget);
            const reelId = button.data('reel-id');
            
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reel_like',
                    reel_id: reelId,
                    nonce: reelAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const icon = button.find('.material-icons');
                        const countElement = button.find('.reel-action-count');
                        
                        if (response.data.liked) {
                            button.addClass('liked');
                            icon.text('favorite');
                        } else {
                            button.removeClass('liked');
                            icon.text('favorite_border');
                        }
                        
                        countElement.text(response.data.count);
                        this.showToast(response.data.message, 'success');
                    } else {
                        this.showToast(response.data.message || reelAjax.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showToast(reelAjax.strings.error, 'error');
                }
            });
        }

        likeCurrentReel() {
            const currentReel = $('.reel-item').eq(this.currentIndex);
            const likeButton = currentReel.find('.reel-like');
            if (likeButton.length) {
                likeButton.trigger('click');
            }
        }

        handleShare(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = $(e.currentTarget);
            const reelId = button.data('reel-id');
            
            this.showShareModal(reelId);
        }

        shareCurrentReel() {
            const currentReel = $('.reel-item').eq(this.currentIndex);
            const shareButton = currentReel.find('.reel-share');
            if (shareButton.length) {
                shareButton.trigger('click');
            }
        }

        showShareModal(reelId) {
            const modal = $('#reel-share-modal');
            modal.data('reel-id', reelId);
            modal.addClass('show');
            $('body').addClass('modal-open');
        }

        handleSharePlatform(e) {
            e.preventDefault();
            
            const button = $(e.currentTarget);
            const platform = button.data('platform');
            const modal = $('#reel-share-modal');
            const reelId = modal.data('reel-id');
            
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reel_share',
                    reel_id: reelId,
                    platform: platform,
                    nonce: reelAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        if (platform === 'copy') {
                            this.copyToClipboard(response.data.share_url);
                        } else {
                            window.open(response.data.share_url, '_blank', 'width=600,height=400');
                        }
                        
                        this.closeModal();
                        this.showToast(response.data.message, 'success');
                    } else {
                        this.showToast(response.data.message || reelAjax.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showToast(reelAjax.strings.error, 'error');
                }
            });
        }

        copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast('Link copiado!', 'success');
                }).catch(() => {
                    this.fallbackCopyTextToClipboard(text);
                });
            } else {
                this.fallbackCopyTextToClipboard(text);
            }
        }

        fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.showToast('Link copiado!', 'success');
            } catch (err) {
                this.showToast('Erro ao copiar link', 'error');
            }
            
            document.body.removeChild(textArea);
        }

        handleAddToCart(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = $(e.currentTarget);
            const productId = button.data('product-id');
            
            // Check if product is variable
            this.checkProductVariations(productId, (hasVariations) => {
                if (hasVariations) {
                    this.showProductModal(productId);
                } else {
                    this.addToCart(productId);
                }
            });
        }

        checkProductVariations(productId, callback) {
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_product_variations',
                    product_id: productId,
                    nonce: reelAjax.nonce
                },
                success: (response) => {
                    if (response.success && response.data.variations.length > 0) {
                        callback(true);
                    } else {
                        callback(false);
                    }
                },
                error: () => {
                    callback(false);
                }
            });
        }

        showProductModal(productId) {
            const modal = $('#reel-product-modal');
            const modalBody = modal.find('#reel-modal-product-content');
            
            modalBody.html('<div class="reel-loading-spinner"></div>');
            modal.addClass('show');
            $('body').addClass('modal-open');
            
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_product_variations',
                    product_id: productId,
                    nonce: reelAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        modalBody.html(response.data.html);
                    } else {
                        this.closeModal();
                        this.showToast(response.data.message || reelAjax.strings.error, 'error');
                    }
                },
                error: () => {
                    this.closeModal();
                    this.showToast(reelAjax.strings.error, 'error');
                }
            });
        }

        addToCart(productId, variationId = 0, variationData = {}, quantity = 1) {
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reel_add_to_cart',
                    product_id: productId,
                    variation_id: variationId,
                    variation_data: variationData,
                    quantity: quantity,
                    nonce: reelAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.closeModal();
                        this.showToast(response.data.message, 'success');
                        
                        // Update cart count if element exists
                        if (response.data.cart_count) {
                            $('.cart-count, .cart-contents-count').text(response.data.cart_count);
                        }
                    } else {
                        this.showToast(response.data.message || reelAjax.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showToast(reelAjax.strings.error, 'error');
                }
            });
        }

        handleAddToWishlist(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = $(e.currentTarget);
            const productId = button.data('product-id');
            
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reel_add_to_wishlist',
                    product_id: productId,
                    nonce: reelAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const icon = button.find('.material-icons');
                        
                        if (response.data.favorited) {
                            button.addClass('favorited');
                            icon.text('favorite');
                        } else {
                            button.removeClass('favorited');
                            icon.text('favorite_border');
                        }
                        
                        this.showToast(response.data.message, 'success');
                    } else {
                        this.showToast(response.data.message || reelAjax.strings.error, 'error');
                    }
                },
                error: () => {
                    this.showToast(reelAjax.strings.error, 'error');
                }
            });
        }

        switchProduct(e) {
            e.preventDefault();
            
            const indicator = $(e.currentTarget);
            const index = indicator.data('index');
            const productSection = indicator.closest('.reel-products-section');
            const slider = productSection.find('.reel-products-slider');
            const productWidth = 280 + 16; // card width + gap
            
            // Update indicators
            productSection.find('.reel-product-indicator').removeClass('active');
            indicator.addClass('active');
            
            // Scroll to product
            slider.scrollLeft(index * productWidth);
        }

        showProductsModal(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // For now, just scroll to products section
            const reelItem = $(e.currentTarget).closest('.reel-item');
            const productsSection = reelItem.find('.reel-products-section');
            
            if (productsSection.length) {
                productsSection[0].scrollIntoView({ behavior: 'smooth' });
            }
        }

        closeModal() {
            $('.reel-modal').removeClass('show');
            $('body').removeClass('modal-open');
        }

        trackView(reelId) {
            if (!reelId) return;
            
            $.ajax({
                url: reelAjax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reel_view',
                    reel_id: reelId,
                    nonce: reelAjax.nonce
                }
            });
        }

        showLoading() {
            $('.reel-loading').addClass('show');
        }

        hideLoading() {
            $('.reel-loading').removeClass('show');
        }

        showToast(message, type = 'info') {
            const toastId = 'toast-' + Date.now();
            const iconMap = {
                success: 'check_circle',
                error: 'error',
                info: 'info'
            };
            
            const toast = $(`
                <div class="reel-toast ${type}" id="${toastId}">
                    <span class="material-icons">${iconMap[type] || 'info'}</span>
                    <span>${message}</span>
                </div>
            `);
            
            $('#reel-toast-container').append(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.fadeOut(300, () => {
                    toast.remove();
                });
            }, 3000);
        }

        handleResize() {
            this.viewportHeight = window.innerHeight;
            
            // Update reel item heights
            $('.reel-item').css('height', this.viewportHeight + 'px');
            
            // Recalculate current position
            this.snapToNearestReel();
        }

        cleanup() {
            if (this.observer) {
                this.observer.disconnect();
            }
            
            // Pause all videos
            $('.reel-video').each((index, video) => {
                if (video.tagName === 'VIDEO') {
                    video.pause();
                }
            });
        }
    }

    // Initialize when DOM is ready
    $(document).ready(() => {
        if ($('.reel-feed-container').length > 0) {
            window.reelPlayer = new ReelPlayer();
        }
    });

})(jQuery);
