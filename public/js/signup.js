document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');
    const advisoryContainer = document.getElementById('advisory-container');
    const gradeLevel = document.getElementById('assigned_grade_level');
    const advisorySection = document.getElementById('advisory_section');

    function toggleAdvisoryFields() {
        if (roleSelect.value === 'Subject Teacher') {
            advisoryContainer.style.display = 'none';
            gradeLevel.disabled = true;
            advisorySection.disabled = true;
        } else {
            advisoryContainer.style.display = 'flex';
            gradeLevel.disabled = false;
            advisorySection.disabled = false;
        }
    }

    toggleAdvisoryFields();
    roleSelect.addEventListener('change', toggleAdvisoryFields);
});