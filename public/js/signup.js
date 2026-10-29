document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');
    const advisoryContainer = document.getElementById('advisory-container');

    function toggleAdvisoryFields() {
        if (roleSelect.value === 'Subject Teacher') {
            advisoryContainer.style.display = 'none';
        } else {
            advisoryContainer.style.display = 'flex'; // Or 'block' depending on your CSS layout
        }
    }

    // Run on page load to set initial state
    toggleAdvisoryFields();

    // Listen for changes when user switches role
    roleSelect.addEventListener('change', toggleAdvisoryFields);
});