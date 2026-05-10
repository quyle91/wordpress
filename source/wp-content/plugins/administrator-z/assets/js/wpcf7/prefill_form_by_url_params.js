function adminz_prefill_cf7_from_url() {
    var queryString = window.location.search;

    if (!queryString) {
        return;
    }

    var params = new URLSearchParams(queryString);

    if (!params || params.size === 0) {
        return;
    }

    // lấy tất cả field trong form CF7
    var inputs = document.querySelectorAll(
        '.wpcf7 form input[name], .wpcf7 form textarea[name], .wpcf7 form select[name]'
    );

    if (!inputs || inputs.length === 0) {
        return;
    }

    inputs.forEach(function (input) {
        var fieldName = input.getAttribute('name');

        if (!fieldName) {
            return;
        }

        if (!params.has(fieldName)) {
            return;
        }

        var value = params.get(fieldName);

        if (value === null || value === '') {
            return;
        }

        // set value cho field
        if (input.type === 'checkbox' || input.type === 'radio') {
            if (input.value === value) {
                console.log(fieldName, value);
                input.checked = true;
            }
            return;
        }

        console.log(fieldName, value);
        input.value = value;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    adminz_prefill_cf7_from_url();
});

// khi CF7 reset form (submit fail / ajax reload)
document.addEventListener('wpcf7reset', function () {
    adminz_prefill_cf7_from_url();
});

document.addEventListener('wpcf7invalid', function () {
    adminz_prefill_cf7_from_url();
});