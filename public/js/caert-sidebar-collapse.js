/**
 * Sous-menus repliables de la barre latérale (Users, Settings, …).
 * Remplace Bootstrap collapse — le CDN DataTables recharge jQuery et casse $.fn.collapse.
 */
(function () {
    'use strict';

    var TRIGGER = '[data-caert-nav-collapse]';

    function findTrigger(panel, sidebar) {
        if (!panel.id) {
            return null;
        }

        return sidebar.querySelector(TRIGGER + '[href="#' + panel.id + '"]');
    }

    function setOpen(panel, trigger, open) {
        var parentLi = trigger ? trigger.closest('li') : null;

        if (open) {
            panel.classList.add('show');
            if (trigger) {
                trigger.classList.remove('collapsed');
                trigger.setAttribute('aria-expanded', 'true');
            }
            if (parentLi) {
                parentLi.classList.add('open');
            }
            return;
        }

        panel.classList.remove('show');
        if (trigger) {
            trigger.classList.add('collapsed');
            trigger.setAttribute('aria-expanded', 'false');
        }
        if (parentLi) {
            parentLi.classList.remove('open');
        }
    }

    function closePanel(panel, sidebar) {
        if (!panel.classList.contains('show')) {
            return;
        }

        setOpen(panel, findTrigger(panel, sidebar), false);
    }

    function closeSiblingPanels(activePanel, sidebar) {
        sidebar.querySelectorAll('.sidebar-subnav.collapse.show').forEach(function (panel) {
            if (panel !== activePanel) {
                closePanel(panel, sidebar);
            }
        });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest(TRIGGER);
        if (!trigger) {
            return;
        }

        var sidebar = trigger.closest('.sidebar');
        if (!sidebar) {
            return;
        }

        var href = trigger.getAttribute('href');
        if (!href || href.charAt(0) !== '#') {
            return;
        }

        var panel = sidebar.querySelector(href);
        if (!panel) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (panel.classList.contains('show')) {
            setOpen(panel, trigger, false);
            return;
        }

        closeSiblingPanels(panel, sidebar);
        setOpen(panel, trigger, true);
    });
})();
