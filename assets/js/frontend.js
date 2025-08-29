jQuery(document).ready(function($) {
    'use strict';

    // Initialize search functionality
    initSearchFilter();

    function initSearchFilter() {
        var searchInput = $('#pgs-search-input');
        var searchButton = $('#pgs-search-button');
        var clearButton = $('#pgs-search-clear');
        var clearSearch = $('#pgs-clear-search');
        var resultsInfo = $('#pgs-search-results-info');
        var resultsCount = $('#pgs-results-count');
        var searchTimeout;

        // Show/hide clear button
        searchInput.on('input', function() {
            var value = $(this).val();
            if (value.length > 0) {
                clearButton.show();
            } else {
                clearButton.hide();
                clearSearch.trigger('click');
            }

            // Real-time search with debounce
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch(value);
            }, 300);
        });

        // Clear search input
        clearButton.on('click', function() {
            searchInput.val('').trigger('input').focus();
        });

        // Search button click
        searchButton.on('click', function() {
            var searchQuery = searchInput.val();
            performSearch(searchQuery);
        });

        // Enter key search
        searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                var searchQuery = $(this).val();
                performSearch(searchQuery);
            }
        });

        // Clear search results
        clearSearch.on('click', function() {
            searchInput.val('');
            clearButton.hide();
            resultsInfo.hide();
            performSearch('');
        });

        // Handle clicks on server-side pagination links (anchors with real hrefs)
        $(document).on('click', '.pgs-pagination-btn:not(.pgs-pagination-current):not([data-page])', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            if (!url) return;

            var page = 1;
            var match = url.match(/page\/(\d+)/);
            if (match && match[1]) page = parseInt(match[1], 10);
            else {
                match = url.match(/[?&](?:paged|page)=([0-9]+)/);
                if (match && match[1]) page = parseInt(match[1], 10);
            }

            var searchQuery = searchInput.val();
            performSearch(searchQuery, page);

            $('html, body').animate({
                scrollTop: $('.pgs-posts-grid').offset().top - 50
            }, 500);
        });

        // Handle clicks on AJAX pagination links created with data-page
        $(document).on('click', '.pgs-pagination-btn[data-page]', function(e) {
            e.preventDefault();
            var page = parseInt($(this).data('page'), 10) || 1;
            var searchQuery = $('#pgs-search-input').val();

            performSearch(searchQuery, page);

            $('html, body').animate({
                scrollTop: $('.pgs-posts-grid').offset().top - 50
            }, 500);
        });
    }

    function performSearch(query, page) {
        page = page || 1;

        var postsGrid = $('.pgs-posts-grid');
        var postsContainer = $('.pgs-posts-container');
        var pagination = $('.pgs-pagination');
        var resultsInfo = $('#pgs-search-results-info');
        var resultsCount = $('#pgs-results-count');

        if (postsGrid.length === 0) return;

        var template = postsGrid.data('template') || 'card';
        var postsPerPage = postsGrid.data('posts-per-page') || 6;

        // Show loading state
        postsContainer.addClass('pgs-loading');
        if (postsContainer.find('.pgs-loading-overlay').length === 0) {
            postsContainer.append('<div class="pgs-loading-overlay"><div class="pgs-spinner"></div></div>');
        }

        $.ajax({
            url: pgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pgs_filter_posts',
                search_query: query,
                posts_per_page: postsPerPage,
                template: template,
                page: page,
                nonce: pgs_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    postsContainer.html(response.data.posts_html || response.data.posts || '');

                    updatePagination(pagination, response.data.current_page || page, response.data.total_pages || 1);

                    if (query) {
                        var totalPosts = response.data.total_posts || (response.data.total_pages ? response.data.total_pages * postsPerPage : 0);
                        resultsCount.text('Found ' + totalPosts + ' posts for "' + query + '"');
                        resultsInfo.show();
                    } else {
                        resultsInfo.hide();
                    }
                } else {
                    console.error('Search failed:', response.data);
                    postsContainer.html('<div class="pgs-no-posts">Search failed. Please try again.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                postsContainer.html('<div class="pgs-no-posts">Search failed. Please try again.</div>');
            },
            complete: function() {
                postsContainer.removeClass('pgs-loading');
                postsContainer.find('.pgs-loading-overlay').remove();
            }
        });
    }

function updatePagination(paginationContainer, currentPage, totalPages) {
    if (totalPages <= 1) {
        paginationContainer.hide();
        return;
    }

    paginationContainer.show();

    var paginationHtml = '';
    var prevIcon = '←';
    var nextIcon = '→';

    function appendPage(page) {
        if (page === currentPage) {
            paginationHtml += '<span class="pgs-pagination-btn pgs-pagination-current">' + page + '</span>';
        } else {
            paginationHtml += '<a href="#" data-page="' + page + '" class="pgs-pagination-btn">' + page + '</a>';
        }
    }

    // Previous button
    if (currentPage > 1) {
        paginationHtml += '<a href="#" data-page="' + (currentPage - 1) + '" class="pgs-pagination-btn pgs-pagination-prev">' + prevIcon + '</a>';
    }

    // Always show first page
    appendPage(1);

    // Calculate middle range
    var start = Math.max(2, currentPage - 1);
    var end = Math.min(totalPages - 1, currentPage + 1);

    // Ellipsis after first page
    if (start > 2) {
        paginationHtml += '<span class="pgs-pagination-ellipsis">...</span>';
    }

    // Middle pages
    for (var i = start; i <= end; i++) {
        appendPage(i);
    }

    // Ellipsis before last page
    if (end < totalPages - 1) {
        paginationHtml += '<span class="pgs-pagination-ellipsis">...</span>';
    }

    // Always show last page
    if (totalPages > 1) {
        appendPage(totalPages);
    }

    // Next button
    if (currentPage < totalPages) {
        paginationHtml += '<a href="#" data-page="' + (currentPage + 1) + '" class="pgs-pagination-btn pgs-pagination-next">' + nextIcon + '</a>';
    }

    paginationContainer.html(paginationHtml);
}


    // Post hover effects
    function initPostHovers() {
        $(document).on('mouseenter', '.pgs-post-card, .pgs-post-list', function() {
            $(this).addClass('pgs-hover');
        });
        $(document).on('mouseleave', '.pgs-post-card, .pgs-post-list', function() {
            $(this).removeClass('pgs-hover');
        });
    }
    initPostHovers();
});
