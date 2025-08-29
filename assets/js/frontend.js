jQuery(document).ready(function($) {
    'use strict';

    // Initialize all functionality
    initSearchFilter();
    initPostAnimations();
    initAccessibility();

    function initSearchFilter() {
        var $searchInput = $('#pgs-search-input');
        var $searchButton = $('#pgs-search-button');
        var $clearButton = $('#pgs-search-clear');
        var $clearSearch = $('#pgs-clear-search');
        var $resultsInfo = $('#pgs-search-results-info');
        var $resultsCount = $('#pgs-results-count');
        var searchTimeout;

        // Enhanced input handling
        $searchInput.on('input', function() {
            var value = $(this).val().trim();
            
            // Show/hide clear button with animation
            if (value.length > 0) {
                $clearButton.fadeIn(200);
            } else {
                $clearButton.fadeOut(200);
                clearSearchResults();
            }

            // Real-time search with debounce
            clearTimeout(searchTimeout);
            if (value.length >= 2 || value.length === 0) {
                searchTimeout = setTimeout(function() {
                    performSearch(value);
                }, 400);
            }
        });

        // Clear search input
        $clearButton.on('click', function() {
            $searchInput.val('').trigger('input').focus();
        });

        // Search button click
        $searchButton.on('click', function() {
            var searchQuery = $searchInput.val().trim();
            performSearch(searchQuery);
        });

        // Enter key search
        $searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                var searchQuery = $(this).val().trim();
                performSearch(searchQuery);
            }
        });

        // Clear search results
        $clearSearch.on('click', function() {
            $searchInput.val('');
            $clearButton.hide();
            $resultsInfo.fadeOut(300);
            performSearch('');
        });

        // Enhanced pagination handling
        $(document).on('click', '.pgs-pagination-btn:not(.pgs-pagination-current)', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var page = 1;
            
            // Get page from data attribute (AJAX pagination)
            if ($btn.data('page')) {
                page = parseInt($btn.data('page'), 10);
            } else {
                // Get page from href (server-side pagination)
                var url = $btn.attr('href');
                if (url) {
                    var match = url.match(/page\/(\d+)/) || url.match(/[?&](?:paged|page)=([0-9]+)/);
                    if (match && match[1]) {
                        page = parseInt(match[1], 10);
                    }
                }
            }

            var searchQuery = $searchInput.val().trim();
            performSearch(searchQuery, page);

            // Smooth scroll to grid
            $('html, body').animate({
                scrollTop: $('.pgs-posts-grid').offset().top - 100
            }, 600, 'easeInOutCubic');
        });
    }

    function performSearch(query, page) {
        page = page || 1;
        query = query || '';

        var $postsGrid = $('.pgs-posts-grid');
        var $postsContainer = $('.pgs-posts-container');
        var $pagination = $('.pgs-pagination');
        var $resultsInfo = $('#pgs-search-results-info');
        var $resultsCount = $('#pgs-results-count');

        if ($postsGrid.length === 0) return;

        var template = $postsGrid.data('template') || 'card';
        var postsPerPage = $postsGrid.data('posts-per-page') || 6;
        var widgetInstance = $postsGrid.data('widget-instance') || {};

        // Enhanced loading state
        showLoadingState($postsContainer);

        $.ajax({
            url: pgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pgs_filter_posts',
                search_query: query,
                posts_per_page: postsPerPage,
                template: template,
                page: page,
                widget_instance: widgetInstance,
                nonce: pgs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Fade out old content
                    $postsContainer.fadeOut(200, function() {
                        // Update content
                        $(this).html(response.data.posts || '');
                        
                        // Update pagination
                        updatePagination($pagination, response.data.current_page || page, response.data.total_pages || 1);
                        
                        // Update search results info
                        if (query) {
                            var totalPosts = response.data.total_posts || 0;
                            $resultsCount.html('Found <strong>' + totalPosts + '</strong> posts for "<em>' + query + '</em>"');
                            $resultsInfo.fadeIn(300);
                        } else {
                            $resultsInfo.fadeOut(300);
                        }
                        
                        // Fade in new content
                        $(this).fadeIn(300);
                        
                        // Reinitialize animations for new content
                        initPostAnimations();
                    });
                } else {
                    handleSearchError($postsContainer, response.data || 'Search failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                handleSearchError($postsContainer, 'Connection error. Please try again.');
            },
            complete: function() {
                hideLoadingState($postsContainer);
            }
        });
    }

    function updatePagination($paginationContainer, currentPage, totalPages) {
        if (totalPages <= 1) {
            $paginationContainer.fadeOut(300);
            return;
        }

        $paginationContainer.show();

        var paginationHtml = '';
        var prevIcon = '←';
        var nextIcon = '→';

        // Previous button
        if (currentPage > 1) {
            paginationHtml += '<a href="#" data-page="' + (currentPage - 1) + '" class="pgs-pagination-btn pgs-pagination-prev">' + prevIcon + '</a>';
        }

        // Page numbers with smart truncation
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);

        // Always show first page
        if (startPage > 1) {
            paginationHtml += '<a href="#" data-page="1" class="pgs-pagination-btn">1</a>';
            if (startPage > 2) {
                paginationHtml += '<span class="pgs-pagination-ellipsis">...</span>';
            }
        }

        // Show page range
        for (var i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                paginationHtml += '<span class="pgs-pagination-btn pgs-pagination-current">' + i + '</span>';
            } else {
                paginationHtml += '<a href="#" data-page="' + i + '" class="pgs-pagination-btn">' + i + '</a>';
            }
        }

        // Always show last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += '<span class="pgs-pagination-ellipsis">...</span>';
            }
            paginationHtml += '<a href="#" data-page="' + totalPages + '" class="pgs-pagination-btn">' + totalPages + '</a>';
        }

        // Next button
        if (currentPage < totalPages) {
            paginationHtml += '<a href="#" data-page="' + (currentPage + 1) + '" class="pgs-pagination-btn pgs-pagination-next">' + nextIcon + '</a>';
        }

        $paginationContainer.html(paginationHtml);
    }

    function showLoadingState($container) {
        $container.addClass('pgs-loading');
        if ($container.find('.pgs-loading-overlay').length === 0) {
            $container.append('<div class="pgs-loading-overlay"><div class="pgs-spinner"></div></div>');
        }
    }

    function hideLoadingState($container) {
        $container.removeClass('pgs-loading');
        $container.find('.pgs-loading-overlay').remove();
    }

    function handleSearchError($container, message) {
        $container.html('<div class="pgs-no-posts">⚠️ ' + message + '</div>');
    }

    function clearSearchResults() {
        $('#pgs-search-results-info').fadeOut(300);
        performSearch('');
    }

    function initPostAnimations() {
        // Intersection Observer for scroll animations
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.animationPlayState = 'running';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });

            $('.pgs-post-card, .pgs-elementor-template').each(function() {
                this.style.animationPlayState = 'paused';
                observer.observe(this);
            });
        }
    }

    function initAccessibility() {
        // Enhanced keyboard navigation
        $(document).on('keydown', '.pgs-search-input', function(e) {
            if (e.key === 'Escape') {
                $(this).blur();
                $('#pgs-search-clear').trigger('click');
            }
        });

        // Focus management for pagination
        $(document).on('click', '.pgs-pagination-btn', function() {
            var $this = $(this);
            setTimeout(function() {
                $('.pgs-posts-container').attr('tabindex', '-1').focus();
            }, 100);
        });

        // Announce search results to screen readers
        var $searchInput = $('#pgs-search-input');
        if ($searchInput.length) {
            $searchInput.attr('aria-describedby', 'pgs-search-results-info');
            
            // Add live region for search results
            if ($('#pgs-search-live-region').length === 0) {
                $('body').append('<div id="pgs-search-live-region" aria-live="polite" aria-atomic="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;"></div>');
            }
        }
    }

    // Add smooth scrolling for better UX
    if (CSS.supports('scroll-behavior', 'smooth')) {
        $('html').css('scroll-behavior', 'smooth');
    } else {
        // Fallback for browsers that don't support smooth scrolling
        $.easing.easeInOutCubic = function(x, t, b, c, d) {
            if ((t /= d / 2) < 1) return c / 2 * t * t * t + b;
            return c / 2 * ((t -= 2) * t * t + 2) + b;
        };
    }

    // Performance optimization: Throttle scroll events
    var scrollTimeout;
    $(window).on('scroll', function() {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        scrollTimeout = setTimeout(function() {
            // Add any scroll-based functionality here
        }, 16); // ~60fps
    });
});