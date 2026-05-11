document.addEventListener('DOMContentLoaded', function () {
    // Tìm nút "Xem thêm".
    const loadMoreButton = document.querySelector('.xemthem');

    // Nếu không tồn tại nút thì dừng.
    if (!loadMoreButton) {
        return;
    }

    /**
     * Kiểm tra sự tồn tại của nút next
     * và cập nhật trạng thái button.
     *
     * @return {HTMLAnchorElement|null}
     */
    function getNextButton() {
        // Tìm nút phân trang "next".
        const nextButton = document.querySelector(
            '.page-numbers.nav-pagination .next.page-number'
        );

        // Nếu không tìm thấy nút next.
        if (!nextButton) {
            loadMoreButton.classList.add('disabled');
            loadMoreButton.setAttribute('disabled', 'disabled');

            return null;
        }

        // Nếu tìm thấy nút next thì enable lại button.
        loadMoreButton.classList.remove('disabled');
        loadMoreButton.removeAttribute('disabled');

        return nextButton;
    }

    // Kiểm tra ngay khi trang vừa load.
    getNextButton();

    // Bắt sự kiện click.
    loadMoreButton.addEventListener('click', function () {
        // Kiểm tra lại trước khi click.
        const nextButton = getNextButton();

        if (!nextButton) {
            return;
        }

        // Trigger click vào nút next.
        nextButton.click();

        // Kiểm tra lại sau khi click.
        // Dùng timeout để chờ DOM cập nhật.
        setTimeout(function () {
            getNextButton();
        }, 1000);
    });
});