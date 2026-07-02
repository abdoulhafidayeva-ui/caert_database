/**
 * Menus d'actions dans les tableaux (hors Bootstrap dropdown — évite le clipping overflow).
 */
(function ($) {
    'use strict';

    var MENU_CLASS = 'caert-row-actions-menu';
    var OPEN_CLASS = 'is-open';
    var TOGGLE_SELECTOR = '[data-caert-actions-toggle]';

    function getOpenMenu() {
        return $('body > .' + MENU_CLASS);
    }

    function isOpenForToggle($toggle) {
        var $open = getOpenMenu();

        if (!$open.length) {
            return false;
        }

        var $linkedToggle = $open.data('caertToggle');

        return $linkedToggle && $linkedToggle[0] === $toggle[0];
    }

    function closeMenu($menu) {
        if (!$menu || !$menu.length) {
            return;
        }

        var $group = $menu.data('caertGroup');
        var $toggle = $menu.data('caertToggle');

        $menu.removeClass(MENU_CLASS + ' ' + OPEN_CLASS + ' show').removeAttr('style');

        if ($group && $group.length) {
            $group.append($menu);
        }

        if ($toggle && $toggle.length) {
            $toggle.attr('aria-expanded', 'false');
        }

        $menu.removeData('caertGroup');
        $menu.removeData('caertToggle');
    }

    function closeAllMenus() {
        getOpenMenu().each(function () {
            closeMenu($(this));
        });

        $('.caert-row-actions .' + MENU_CLASS).each(function () {
            closeMenu($(this));
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

    $(document).on('click', TOGGLE_SELECTOR, function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $toggle = $(this);

        if (isOpenForToggle($toggle)) {
            closeAllMenus();
            return;
        }

        closeAllMenus();

        var $group = $toggle.closest('.caert-row-actions');
        var $menu = $group.children('.dropdown-menu').first();

        if (!$menu.length) {
            return;
        }

        $menu.data('caertGroup', $group);
        $menu.data('caertToggle', $toggle);
        $('body').append($menu.addClass(MENU_CLASS + ' ' + OPEN_CLASS + ' show'));
        positionMenu($toggle, $menu);
        $toggle.attr('aria-expanded', 'true');
    });

    $(document).on('click', function (e) {
        if ($(e.target).closest(TOGGLE_SELECTOR).length || $(e.target).closest('.' + MENU_CLASS).length) {
            return;
        }

        closeAllMenus();
    });

    $(window).on('resize scroll', closeAllMenus);
    $(document).on('draw.dt', closeAllMenus);
})(window.jQuery);
