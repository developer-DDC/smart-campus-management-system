document.addEventListener("DOMContentLoaded", function () {
    const forms = document.querySelectorAll("form");

    forms.forEach(function (form) {
        form.addEventListener("submit", function () {
            const requiredInputs = form.querySelectorAll("input[required], select[required], textarea[required]");
            let valid = true;

            requiredInputs.forEach(function (input) {
                if (!input.value.trim()) {
                    valid = false;
                }
            });

            if (!valid) {
                alert("Please fill in all required fields.");
            }
        });
    });
});
