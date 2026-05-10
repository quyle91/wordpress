(function () {
    'use strict';

    const Adminz_admin = {
        init() {
            this.script_debug = adminz_js.script_debug;
            window.addEventListener('resize', () => this.onWindowResize());
            document.addEventListener('DOMContentLoaded', () => this.onDOMContentLoaded());
        },

        onWindowResize() {
            // Something here
        },

        onDOMContentLoaded() {

            // adminz_click_to_copy
            document.querySelectorAll('.adminz_click_to_copy').forEach(element => {
                this.click_to_copy_init(element);
            });

            // adminz_fetch
            document.querySelectorAll('.adminz_fetch').forEach(element => {
                this.fetch_init(element);
            });

            // adminz_toggle
            document.querySelectorAll('.adminz_toggle').forEach(element => {
                this.toggle_init(element);
            });

            // adminz_replace_image
            document.querySelectorAll('.adminz_replace_image').forEach(element => {
                this.replace_image(element);
            });

            // adminz_open_wpmedia
            document.querySelectorAll('.adminz_open_wpmedia').forEach(element => {
                this.open_wpmedia(element);
            });

            // adminz_menus_toggle_button
            document.querySelectorAll('.adminz_menus_toggle_button').forEach(element => {
                this.adminz_menus_toggle_button(element);
            });

            // select_folders
            document.querySelectorAll('.select_folders').forEach(element => {
                this.select_folders(element);
            });

            // zip_downloader
            document.querySelectorAll('.zip_downloader').forEach(element => {
                this.zip_downloader(element);
            });

            // move_to_must_use_plugins
            document.querySelectorAll('.move_to_must_use_plugins').forEach(element => {
                this.move_to_must_use_plugins(element);
            });

            if (this.script_debug) {
                console.log(this);
            }

            // options page
            this.setup_option_page();

            // crawl
            this.setup_crawl();
        },

        // ---------------- Your custom event here ---------------- //

        move_to_must_use_plugins(element) {
            element.querySelector('.xbutton').addEventListener('click', function () {
                const statusEl = element.querySelector('.xstatus');
                const folderPathInput = element.querySelector('.folder-path');

                if (statusEl) statusEl.textContent = 'Processing...';

                const folderPath = folderPathInput ? folderPathInput.value : '';

                if (!folderPath) {
                    if (statusEl) statusEl.textContent = 'Please enter a valid folder path.';
                    return;
                }

                //Fetch
                (async () => {
                    try {
                        const url = adminz_js.ajax_url;
                        const formData = new FormData();
                        formData.append('action', 'adminz_move_to_must_use');
                        formData.append('folder_path', folderPath);
                        formData.append('nonce', adminz_js.nonce);
                        //console.log('Before Fetch:', formData.get('data'));

                        const _fetch = await fetch(url, {
                            method: 'POST',
                            body: formData,
                        });

                        if (!_fetch.ok) {
                            throw new Error('Network response was not ok');
                        }

                        const response = await _fetch.json(); // response.text()
                        statusEl.textContent = response.data;
                        if (response.success) {
                            //Code here
                            console.log(response.data);
                        } else {
                            //console.error('Error:', response.message);
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                    }
                })();

            });
        },

        zip_downloader(element) {
            element.querySelector('.xbutton').addEventListener('click', function () {
                const statusEl = element.querySelector('.xstatus');
                const folderPathInput = element.querySelector('.folder-path');

                if (statusEl) statusEl.textContent = 'Processing...';

                const folderPath = folderPathInput ? folderPathInput.value : '';

                if (!folderPath) {
                    if (statusEl) statusEl.textContent = 'Please enter a valid folder path.';
                    return;
                }

                //Fetch
                const xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.responseType = 'blob';

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const blob = xhr.response;
                        const link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);

                        let filename = 'download.zip';
                        const contentDisposition = xhr.getResponseHeader('Content-Disposition');
                        const matches = contentDisposition && /filename="([^"]*)"/.exec(contentDisposition);
                        if (matches && matches[1]) {
                            filename = matches[1];
                        }

                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        if (statusEl) statusEl.textContent = 'Download complete.';
                    } else {
                        if (statusEl) statusEl.textContent = 'An error occurred.';
                    }
                };

                xhr.onerror = function () {
                    if (statusEl) statusEl.textContent = 'An error occurred.';
                };

                const formData = new FormData();
                formData.append('action', 'adminz_zip_download');
                formData.append('folder_path', folderPath);

                xhr.send(formData);
            });
        },

        select_folders(element) {
            element.querySelector('.suggestions').addEventListener('click', function (e) {
                const target = e.target.closest('.theme-suggestion, .plugin-suggestion');
                if (target) {
                    e.preventDefault();
                    const folderPathInput = element.querySelector('.folder-path');
                    if (folderPathInput) {
                        folderPathInput.value = target.dataset.path;
                    }
                }
            });
        },

        adminz_menus_toggle_button(element) {
            document.querySelectorAll("#menu-to-edit .menu-item").forEach(function (menuItem) {
                let itemId = menuItem.id.replace("menu-item-", "");

                // Kiểm tra xem có menu nào có parent-id là itemId không
                let children = document.querySelectorAll(`.menu-item-data-parent-id[value="${itemId}"]`);

                if (children.length) {
                    // Tạo nút toggle
                    let toggleButton = document.createElement("span");
                    toggleButton.textContent = "Toggle";
                    toggleButton.className = "adminz_toggle_submenu item-type";

                    // Chèn nút vào tiêu đề của menu item
                    let title = menuItem.querySelector(".item-controls");
                    if (title) {
                        title.prepend(toggleButton);
                    }

                    // Hàm đệ quy để ẩn/hiện tất cả các cấp con
                    function toggleChildren(parentId, isHiding) {
                        document.querySelectorAll(`.menu-item-data-parent-id[value="${parentId}"]`).forEach(child => {
                            let childLi = child.closest('li');
                            if (childLi) {
                                // Nếu parent đang ẩn, phải đóng tất cả children toggled
                                if (isHiding && childLi.classList.contains('adminz_menu_item_togged')) {
                                    childLi.classList.remove('adminz_menu_item_togged');
                                }

                                // Toggle ẩn/hiện
                                childLi.classList.toggle('hidden', isHiding);

                                // Gọi đệ quy cho cấp con của child
                                let childId = childLi.id.replace("menu-item-", "");
                                toggleChildren(childId, isHiding);
                            }
                        });
                    }

                    // Sự kiện click toggle
                    toggleButton.addEventListener("click", function (event) {
                        event.preventDefault();
                        let isHiding = !menuItem.classList.contains('adminz_menu_item_togged');

                        // Toggle chính nó
                        menuItem.classList.toggle('adminz_menu_item_togged', isHiding);

                        // Đệ quy ẩn/hiện tất cả các cấp con
                        toggleChildren(itemId, isHiding);
                    });
                }
            });


        },

        open_wpmedia(element) {
            element.addEventListener('click', () => {
                let data_config = {
                    title: 'Wordpress library',
                    button: {
                        text: 'Select'
                    },
                    multiple: true
                };

                if (element.getAttribute('data-config')) {
                    data_config = JSON.parse(element.getAttribute('data-config'));
                }

                const mediaFrame = wp.media(data_config);
                mediaFrame.on('select', () => {
                    const selection = mediaFrame.state().get('selection');
                    const images = [];
                    selection.each((attachment) => {
                        images.push(attachment.toJSON());
                    });

                    if (this.script_debug) {
                        console.log("---------- open_wpmedia ---------------", images);
                    }

                    // run callback if exists
                    const callback = element.getAttribute('data-callback');
                    if (callback) {
                        document.dispatchEvent(
                            new CustomEvent(
                                callback,
                                {
                                    detail: {
                                        context: element,
                                        images: images,
                                    }
                                }
                            )
                        );
                    }
                });

                // create wp media box
                mediaFrame.open();
            });
        },

        replace_image(element) {
            element.addEventListener('change', function () {
                const action = element.getAttribute('data-action');
                const _response = document.querySelector(element.getAttribute('data-response'));

                if (!action) {
                    alert(' action is required!');
                    return;
                }

                if (!_response) {
                    alert(' response is required!');
                    return;
                }

                if (element.files.length === 0) {
                    alert('No file selected!');
                    return;
                }

                const file = element.files[0];
                _response.textContent = '';
                element.setAttribute('disabled', 'disabled');

                // Fetch 
                (async () => {
                    try {
                        const url = adminz_js.ajax_url;
                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('file', file);
                        formData.append('nonce', adminz_js.nonce);
                        //console.log('Before Fetch:', formData.get('data');

                        const response = await fetch(url, {
                            method: 'POST',
                            body: formData,
                        });
                        element.removeAttribute('disabled');
                        element.value = "";

                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }

                        const data = await response.json(); // reponse.text()
                        console.log(data);
                        if (data.success) {
                            //Code here
                            console.log(_response);
                            _response.innerHTML = data.data;
                        } else {
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                    }
                })();
            });
        },

        toggle_init(element) {
            element.onclick = function () {
                const selector = element.getAttribute('data-toggle');
                if (!selector) {
                    alert('selector not found!');
                    return;
                }
                const target = document.querySelector(selector);
                target.classList.toggle('hidden');
            }
        },

        fetch_init(element) {
            element.onclick = function () {
                const _action = element.getAttribute('data-action');
                console.log("Action:", _action);
                const _response = document.querySelector(element.getAttribute('data-response'));

                if (!_response) { alert('no response to fetch'); return; }
                if (!_action) { alert('no action to fetch'); return; }

                _response.textContent = '';

                // clear all other
                document.querySelectorAll(".adminz_response").forEach(item => {
                    item.innerHTML = "";
                })
                element.setAttribute('disabled', 'disabled');

                // Fetch 
                (async () => {
                    try {
                        const url = adminz_js.ajax_url;
                        const form = element.closest("form");
                        const formData = new FormData(form);

                        formData.append('action', _action);
                        formData.append('nonce', adminz_js.nonce);
                        console.log("FETCH INIT ------------- ", formData);

                        const response = await fetch(url, {
                            method: 'POST',
                            body: formData,
                        });
                        element.removeAttribute('disabled');

                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }

                        const data = await response.json(); // reponse.text()
                        console.log(data);
                        console.log(_action);
                        if (data.success) {
                            //Code here
                            _response.innerHTML = data.data;

                            document.dispatchEvent(
                                new CustomEvent(
                                    _action,
                                    {
                                        detail: {
                                            context: this,
                                            data: data,
                                        }
                                    }
                                )
                            );
                        } else {
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                    }
                })();

            }
        },

        click_to_copy_init(element) {
            element.onclick = function () {
                const text = element.getAttribute('data-text');
                if (text) {
                    const textArea = document.createElement("textarea");
                    textArea.value = text;
                    textArea.style.position = "fixed";  // Tránh việc textarea làm thay đổi layout trang web
                    textArea.style.opacity = "0";  // Làm cho textarea vô hình
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        alert('Copied to clipboard: \n' + text);
                    } catch (err) {
                        alert('Error to copy!');
                    }
                    document.body.removeChild(textArea);
                }
            }
        },

        setup_option_page() {
            const adminz_wrap_h2 = document.querySelectorAll('.adminz_wrap form h2');
            if (adminz_wrap_h2) {
                adminz_wrap_h2.forEach(h2 => {
                    h2.style.position = 'relative';

                    // Tạo span để bọc nội dung hiện tại
                    const textSpan = document.createElement('span');
                    textSpan.textContent = h2.textContent;
                    h2.textContent = ''; // Xóa nội dung hiện tại để thêm lại dưới dạng phần tử con
                    h2.appendChild(textSpan);

                    // Tạo icon toggle
                    const toggleSpan = document.createElement('span');
                    toggleSpan.classList.add('dashicons', 'dashicons-sort');
                    toggleSpan.style.cursor = 'pointer';
                    toggleSpan.style.marginLeft = '10px';
                    toggleSpan.style.color = '#2271b1';
                    toggleSpan.style.position = 'absolute';
                    toggleSpan.style.right = '0px';
                    toggleSpan.style.fontSize = '20px';
                    toggleSpan.style.fontWeight = '400';

                    h2.appendChild(toggleSpan);

                    const nextTable = h2.nextElementSibling;
                    if (nextTable && nextTable.tagName.toLowerCase() === 'table') {
                        toggleSpan.addEventListener('click', () => {
                            nextTable.style.display = nextTable.style.display === 'none' ? 'table' : 'none';
                        });
                    }
                });
            }
        },

        setup_crawl() {

            document.addEventListener('run_adminz_import_from_category', function (event) {
                const action = 'run_adminz_import_from_post';
                const button = event.detail.context;
                this.setup_crawl_run_multiples(action, button);
            }.bind(this));

            document.addEventListener('run_adminz_import_from_product_category', function (event) {
                const action = 'run_adminz_import_from_product';
                const button = event.detail.context;
                this.setup_crawl_run_multiples(action, button);
            }.bind(this));

            document.addEventListener('run_adminz_import_images', function (event) {
                const action = 'run_adminz_import_image';
                const button = event.detail.context;
                this.setup_crawl_run_multiples(action, button);
            }.bind(this));
        },

        setup_crawl_run_multiples(action, button) {
            const wrap = document.querySelector(button.getAttribute('data-response'));
            const rows = wrap.querySelectorAll('tr');
            let rowUrlPairs = [];

            if (rows) {
                rows.forEach(row => {
                    const url = row.getAttribute('data-url');
                    if (url) {
                        rowUrlPairs.push({ row, url });
                    }

                    const runButton = row.querySelector('.run');
                    runButton.onclick = () => {
                        this.setup_crawl_run_single(row, url, action);
                    }
                });
            }

            // Khởi động xử lý tuần tự các URL
            let sequence = Promise.resolve();
            rowUrlPairs.forEach(({ row, url }) => {
                sequence = sequence.then(() => this.setup_crawl_run_single(row, url, action)
                    .catch(error => {
                        console.error(`Error processing ${url}:`, error);
                        // Tiếp tục chuỗi Promise ngay cả khi có lỗi
                        return Promise.resolve();
                    })
                );
            });

            sequence.then(() => {
                alert("All URLs have been processed.");
                console.log("All URLs have been processed.");
            });
        },

        setup_crawl_run_single(row, url, action) {
            return new Promise((resolve, reject) => {
                try {
                    const apiUrl = adminz_js.ajax_url;
                    const form = row.closest("form");
                    const formData = new FormData(form);
                    formData.append('nonce', adminz_js.nonce);
                    formData.append('action', action);
                    formData.append('url', url);

                    // const apiUrl = adminz_js.ajax_url;
                    // const formData = new FormData();
                    // formData.append('action', action);
                    // formData.append('url', url);
                    // formData.append('nonce', adminz_js.nonce);

                    // debug
                    // formData.forEach((value, key) => {
                    //     console.log(key, value);
                    // });
                    // return;

                    fetch(apiUrl, {
                        method: 'POST',
                        body: formData,
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log(`Fetched data from ${url}:`, data);
                            if (data.success) {
                                row.querySelector('.result').innerHTML = data.data;
                                // row.querySelector('button').setAttribute('disabled', 'disabled');
                                resolve();
                            } else {
                                reject(`Failed to fetch data from ${url}`);
                            }
                        })
                        .catch(error => {
                            console.error(`Fetch error for ${url}:`, error);
                            reject(error);
                        });
                } catch (error) {
                    console.error('Fetch error:', error);
                    reject(error);
                }
            });
        },

        // ---------------- Default Methods ----------------------- //

        ___check_click_element(element) {
            element.onclick = function (event) {
                console.log(event.currentTarget);
            }
        },

        _setDemoData(element) {
            const demoData = {
                'text': 'Demo Text',
                'checkbox': true,
                'radio': 'option2',
                'password': 'DemoPassword123',
                'email': 'demo@example.com',
                'tel': '123-456-7890',
                'number': 42,
                'date': '2024-01-17',
                'time': '12: 34',
                'url': 'https: //www.example.com',
                'search': 'Search query',
                'color': '#3498db',
                'textarea': 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Aspernatur consequuntur deserunt nam veniam aliquid libero porro ullam.',
            };
            const forms = element.querySelectorAll('form');
            forms.forEach(form => {
                const formFields = form.querySelectorAll('input, textarea, select');
                formFields.forEach(field => {
                    const fieldType = field.tagName.toLowerCase() === 'textarea' ? 'textarea' : field.tagName.toLowerCase() === 'select' ? 'select' : field.getAttribute('type');
                    const fieldValue = demoData[fieldType
                    ];
                    if (fieldType && !field.value) {
                        switch (fieldType) {
                            case 'search':
                                field.value = fieldValue !== undefined ? fieldValue : '';
                                break;
                            case 'checkbox':
                                field.checked = fieldValue || false;
                                break;
                            case 'radio':
                                if (field.value === fieldValue) {
                                    field.checked = true;
                                }
                                break;
                            case 'color':
                                field.value = fieldValue || '#ffffff';
                                break;
                            case 'textarea':
                                field.value = fieldValue !== undefined ? fieldValue : '';
                                break;
                            case 'select':
                                const options = field.querySelectorAll('option');
                                options.forEach(option => {
                                    option.selected = true;
                                });
                                break;
                            default:
                                field.value = fieldValue;
                                break;
                        }
                    }
                });
            });
        },
    };

    Adminz_admin.init();
    window.Adminz_admin = Adminz_admin;
})();


