(function () {
    'use strict';

    class AdminzFamilyTreeDefault {
        constructor() {
            window.addEventListener('resize', () => this.onWindowResize());
            document.addEventListener('DOMContentLoaded', () => this.onDOMContentLoaded());
        };

        onDOMContentLoaded = () => {

            // adminz_family_tree_default
            document.querySelectorAll('.adminz_family_tree_default').forEach(element => {
                this.familyTree_setup(element);
                element.addEventListener('scroll', () => {
                    this.familyTree_setup(element);
                });
            });
        };

        onWindowResize = () => {
            // adminz_family_tree_default
            document.querySelectorAll('.adminz_family_tree_default').forEach(element => {
                this.familyTree_setup(element);
            });
        }

        familyTree_setup = (element) => {
            const svg = element.querySelector('svg');
            svg.innerHTML = '';

            element.querySelectorAll('.item').forEach(parent => {
                const parent_id = parent.getAttribute('data-id');
                const children = document.querySelector(".group-" + parent_id);
                if (children) {
                    const drawLine = (element, parent, child) => {
                        const svg = element.querySelector('svg');
                        const parentRect = parent.getBoundingClientRect();
                        const childRect = child.getBoundingClientRect();
                        const elementRect = element.getBoundingClientRect();

                        // Tính toán lại các tọa độ dựa trên vị trí cuộn hiện tại
                        const scrollLeft = element.scrollLeft;
                        const scrollTop = element.scrollTop;

                        let startX = Math.round(parentRect.left + parentRect.width / 2 - elementRect.left + scrollLeft);
                        let startY = parentRect.bottom - elementRect.top + scrollTop;
                        let endX = Math.round(childRect.left + childRect.width / 2 - elementRect.left + scrollLeft);
                        let endY = childRect.top - elementRect.top - 14 + scrollTop;
                        let midY = startY + 15;

                        const fixY = parent.getAttribute('data-fixy');
                        if (fixY) {
                            midY += 7 * fixY;
                        }

                        let path;
                        if (Math.abs(startX - endX) <= 4) {
                            path = `M${startX},${startY} V${endY}`;
                        } else {
                            const horizontalOffset = 5 * Math.sign(endX - startX);

                            path = `M${startX},${startY} 
                            V${midY - 5} 
                            Q${startX},${midY},${startX + horizontalOffset},${midY} 
                            H${endX - horizontalOffset} 
                            Q${endX},${midY},${endX},${midY + 5} 
                            V${endY}`;
                        }

                        const newPath = document.createElementNS("http://www.w3.org/2000/svg", "path");
                        newPath.classList.add('parent-' + parent_id);
                        newPath.setAttribute("d", path);

                        svg.appendChild(newPath);
                    };

                    drawLine(element, parent, children);
                }

                // hover
                parent.addEventListener('mouseover', (e) => {
                    parent.classList.add('active');
                    if (children) {
                        children.classList.add('active');
                    }
                    const path = document.querySelector("path.parent-" + parent_id);
                    if (path) {
                        path.classList.add('active');
                        // move path to top
                        path.remove();
                        svg.appendChild(path);
                    }
                });

                parent.addEventListener('mouseout', (e) => {
                    parent.classList.remove('active');
                    if (children) {
                        children.classList.remove('active');
                    }
                    const path = document.querySelector("path.parent-" + parent_id);
                    if (path) {
                        path.classList.remove('active');
                    }
                });
            });
        };
    }

    window.AdminzFamilyTreeDefault = new AdminzFamilyTreeDefault();

})();