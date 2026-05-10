(function () {
    'use strict';

    class AdminzFamilyTreeClassic {
        constructor() {
            window.addEventListener('resize', () => this.onWindowResize());
            document.addEventListener('DOMContentLoaded', () => this.onDOMContentLoaded());
        }

        onDOMContentLoaded = () => {
            document.querySelectorAll('.adminz_family_tree_classic').forEach(element => {
                this.familyTree_setup(element);

                element.addEventListener('scroll', () => {
                    this.familyTree_setup(element);
                });

                element.addEventListener('click', (e) => {
                    this.onToggleClick(e, element);
                });
            });
        };

        onWindowResize = () => {
            document.querySelectorAll('.adminz_family_tree_classic').forEach(element => {
                this.familyTree_setup(element);
            });
        };

        familyTree_setup = (element) => {
            const items = element.querySelectorAll('.tree-item');

            for (const item of items) {
                const toggle = item.querySelector(':scope > .tree-row > .tree-toggle');

                if (!toggle) {
                    continue;
                }

                const children = item.querySelector(':scope > .tree-children');

                if (!children) {
                    continue;
                }

                const isHidden = children.classList.contains('hidden');

                if (isHidden) {
                    toggle.textContent = '+';
                    toggle.setAttribute('aria-expanded', 'false');
                } else {
                    toggle.textContent = '-';
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }
        };

        onToggleClick = (e, element) => {
            const toggle = e.target.closest('.tree-toggle');

            if (!toggle) {
                return;
            }

            if (!element.contains(toggle)) {
                return;
            }

            const item = toggle.closest('.tree-item');

            if (!item) {
                return;
            }

            const children = item.querySelector(':scope > .tree-children');

            if (!children) {
                return;
            }

            const isHidden = children.classList.contains('hidden');

            if (isHidden) {
                this.openDirectChildren(item);
                return;
            }

            this.closeAllChildren(item);
        };

        openDirectChildren = (item) => {
            const children = item.querySelector(':scope > .tree-children');

            if (!children) {
                return;
            }

            // mở con trực tiếp
            children.classList.remove('hidden');

            const toggle = item.querySelector(':scope > .tree-row > .tree-toggle');

            if (toggle) {
                toggle.textContent = '-';
                toggle.setAttribute('aria-expanded', 'true');
            }
        };

        closeAllChildren = (item) => {
            // đóng toàn bộ con cháu
            const childrenLists = item.querySelectorAll('.tree-children');

            for (const list of childrenLists) {
                list.classList.add('hidden');
            }

            const toggles = item.querySelectorAll('.tree-toggle');

            for (const toggle of toggles) {
                toggle.textContent = '+';
                toggle.setAttribute('aria-expanded', 'false');
            }
        };
    }

    window.AdminzFamilyTreeClassic = new AdminzFamilyTreeClassic();

})();
