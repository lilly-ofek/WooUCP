(function ($) {
    'use strict';

    $(window).on('load', function () {
        const tabs = $('.ucp-tab-link');
        const contents = $('.ucp-tab-content');

        if (!tabs.length) return;

        function switchTab(targetId) {
            tabs.removeClass('active');
            contents.hide().removeClass('active');

            const activeTab = $(`.ucp-tab-link[href="#${targetId}"]`);
            const activeContent = $('#' + targetId);

            if (activeTab.length && activeContent.length) {
                activeTab.addClass('active');
                activeContent.show().addClass('active');
                localStorage.setItem('ucp_active_tab', targetId);
            }
        }

        tabs.on('click', function (e) {
            e.preventDefault();
            const target = $(this).attr('href').replace('#', '');
            switchTab(target);
        });

        // Restore active tab
        const lastTab = localStorage.getItem('ucp_active_tab') || 'dashboard';
        switchTab(lastTab);

        // Move WP notices outside our wrap to keep header clean
        function moveNotices() {
            const anchor = $('#ucp-notices-anchor');
            const wrap = $('.ucp-settings-wrap');
            const body = $('.wp-admin');

            // Collect all typical WP notices
            const notices = $('.notice, .updated, .error, .woocommerce-message, .is-dismissible');

            if (anchor.length && notices.length) {
                notices.each(function () {
                    const notice = $(this);
                    // Skip if it's already in the anchor or not in the main content area
                    if (notice.parent().is(anchor) || !notice.closest('#wpbody-content').length) return;

                    anchor.append(notice);
                });
            }
        }

        // Run immediately and after a short delay for secondary notices
        moveNotices();
        setTimeout(moveNotices, 500);
        setTimeout(moveNotices, 2000);
    });

})(jQuery);
