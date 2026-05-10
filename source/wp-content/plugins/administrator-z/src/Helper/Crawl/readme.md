Chức năng Crawl tool Refactor: nhập url để insert post vào wordpress, hình ảnh cũng được tải về wordpress

Thư viện:
"symfony/dom-crawler": "^7.4",
"symfony/css-selector": "^7.4"

Options: 
xem file /wp-content/plugins/administrator-z/src/Controller/Tools.php

1. Logs: bắt buộc bật để chạy tool crawl. mỗi lần thực hiện run thì sẽ lưu vào db, Mục đích là so sánh với lần tiếp theo để lấy next url cho crawl
2. Post: Url bất kỳ, Nút check: kiểm tra trước khi run, nút Run: Thực hiện crawl
3. category: Category post bất kỳ, Nút check: kiểm tra danh sách post tìm được trước khi run, nút Run: Thực hiện crawl
4. product: tương tự post nhưng là cho product
5. Product category: tương tự category nhưng là cho product
6. Images only: Tìm tất cả ảnh trong url để insert, image url và wp_image inserted được lưu vào db
7. Css Selector: các option để lấy thông tin từ html
8. Check exists on wp_posts: 
    <thay đổi thành> luôn luôn bật, 
    trước khi tạo ảnh mới trong wp library thì phải kiểm tra có id trong table log hay không.
9. Load response from transient: 
    <thay đổi thành> luôn luôn bật, 
    tiết kiệm thời gian bằng cách cache html vào db.
10. Content Fix: 
    Remove Attributes for Tags: 
    Remove HTML Tags: tìm và xóa theo thẻ.
    Removes the number of elements from the First: xóa một số thẻ ở header 
    Removes the number of elements from the End: xóa một số thẻ ở footer
11. Search and replace content
    Tìm và thay thể nội dung của htlm
12. Post type: Cho phép chọn post type thay vì cố định là post
13. Post parent: cho phép chọn post parent id
14. Fixed taxonomy terms: Cho phép chọn term taxonomy số nhiều
15. Setup Woocommerce:
    Price fix: xóa đi 2 số 0, ví dụ từ 2000 -> 20
16. Crawl Cron
    Items per time: số lượng item một lần chạy
    Crawl products by url: Chọn term product cat và url product cat tương ứng để crawl vào term product cat
    Crawl posts by url: chọn term category và url posts tương ứng để crawl vào term category
    Files: ví dụ về setup cron