/**
 * Menus d'actions dans les tableaux (hors Bootstrap dropdown — évite le clipping overflow).
 */
(function ($) {
    'use strict';

    var MENU_CLASS = 'caert-row-actions-menu';

    function closeAllMenus() {
        $('.' + MENU_CLASS).each(function () {
            var $menu = $(this);
            var $group = $menu.data('caertGroup');

            $menu.removeClass('show is-open').removeAttr('style');
            if ($group && $group.length) {
                $group.append($menu);
            }
            $menu.removeData('caertGroup');
        });
    }

    function positionMenu($toggle, $menu) {
        var offset = $toggle.offset();
        var menuWidth = $menu.outerWidth();
        var left = offset.left + $toggle.outerWidth() - menuWidth;

        $menu.css({
            display: 'block',
            position: 'absolute',
            top: offset.top + $toggle.outerHeight(),
            left: Math.max(8, left),
            zIndex: 1060,
        });
    }

    $(document).on('click', '[data-caert-actions-toggle]', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $toggle = $(this);
        var $group = $toggle.closest('.caert-row-actions');
        var $menu = $group.find('.dropdown-menu').first();

        if (!$menu.length) {
            return;
        }

        var wasOpen = $menu.hasClass('is-open');

        closeAllMenus();

        if (!wasOpen) {
            $menu.data('caertGroup', $group);
            $('body').append($menu.addClass(MENU_CLASS + ' is-open show'));
            positionMenu($toggle, $menu);
        }
    });

    $(document).on('click', function () {
        closeAllMenus();
    });

    $(document).on('click', '.' + MENU_CLASS, function (e) {
        e.stopPropagation();
    });

    $(window).on('resize scroll', closeAllMenus);
})(window.jQuery);
