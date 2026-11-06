document.addEventListener("DOMContentLoaded", function() {
    // window.allSections comes from the global variable defined in the PHP file
    const allSections = window.allSections || [];
    
    const gradeSelect = document.getElementById('assigned_grade_level');
    const sectionSelect = document.getElementById('advisory_section');

    if (!gradeSelect || !sectionSelect) return;

    function updateSections() {
        const selectedGrade = gradeSelect.value;
        
        // Clear existing options
        sectionSelect.innerHTML = '<option value="" disabled selected>Select Section</option>';
        
        // Filter and append matching sections
        const filteredSections = allSections.filter(sec => sec.grade_level === selectedGrade);
        filteredSections.forEach(sec => {
            const option = document.createElement('option');
            option.value = sec.section_id;
            option.textContent = sec.section_name;
            sectionSelect.appendChild(option);
        });
    }

    // Run on page load if a grade is already selected
    if (gradeSelect.value) {
        updateSections();
    }

    // Run whenever the user changes the grade dropdown
    gradeSelect.addEventListener('change', updateSections);
});