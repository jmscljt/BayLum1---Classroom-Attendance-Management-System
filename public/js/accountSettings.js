document.addEventListener('DOMContentLoaded', function () {
    const editButton = document.getElementById('editAssignedSection');
    const form = document.getElementById('settingsSectionForm');
    const gradeSelect = document.getElementById('settingsGrade');
    const sectionSelect = document.getElementById('settingsSection');

    const grades = [...new Set(accountSections.map(section => section.grade_level))];
    grades.forEach(grade => {
        const option = document.createElement('option');
        option.value = grade;
        option.textContent = grade;
        gradeSelect.appendChild(option);
    });

    if (grades.includes(assignedGrade)) {
        gradeSelect.value = assignedGrade;
    }

    function loadSections() {
        sectionSelect.innerHTML = '<option value="" disabled>Select Section</option>';

        accountSections
            .filter(section => section.grade_level === gradeSelect.value)
            .forEach(section => {
                const option = document.createElement('option');
                option.value = section.section_id;
                option.textContent = section.section_name;

                if (section.section_name === assignedSection) {
                    option.selected = true;
                }

                sectionSelect.appendChild(option);
            });
    }

    loadSections();
    gradeSelect.addEventListener('change', loadSections);

    editButton.addEventListener('click', function () {
        form.hidden = !form.hidden;
    });
});
