document.addEventListener("DOMContentLoaded", function() {
    
    // --- HINT 1 & 2: Toggle the 'highlight' class on focus and blur ---
    // Select all elements that have the 'hilightable' class
    const hilightableFields = document.querySelectorAll('.hilightable');

    hilightableFields.forEach(function(field) {
        // Add highlight class when the user clicks into the field
        field.addEventListener('focus', function() {
            this.classList.add('highlight');
        });

        // Remove highlight class when the user clicks away from the field
        field.addEventListener('blur', function() {
            this.classList.remove('highlight');
        });
    });

    // --- HINT 3: Form validation on submit ---
    const form = document.getElementById('mainForm');

    form.addEventListener('submit', function(event) {
        // Select all elements that have the 'required' class
        const requiredFields = document.querySelectorAll('.required');
        let hasError = false;

        requiredFields.forEach(function(field) {
            // First, remove any existing error class to reset the state
            field.classList.remove('error');

            // Check if the field is empty (using trim() to ignore accidental spaces)
            if (field.value.trim() === '') {
                field.classList.add('error'); // Add the error styling
                hasError = true;              // Flag that an error occurred
            }
        });

        // If any required field was empty, prevent the form from submitting
        if (hasError) {
            event.preventDefault();
        }
    });
});