const studentModal = document.getElementById('studentModal');
const openStudentModalButton = document.getElementById('openStudentModal');
const closeStudentModalButton = document.getElementById('closeStudentModal');
const cancelStudentModalButton = document.getElementById('cancelStudentModal');

function updateStudentSectionOptions() {
    const gradeSelect = document.getElementById('studentGradeLevel');
    const sectionSelect = document.getElementById('studentSection');
    if (!gradeSelect || !sectionSelect) return;

    const selectedGrade = gradeSelect.value;
    let hasVisibleOption = false;

    Array.from(sectionSelect.options).forEach(function(option) {
        if (!option.value) return;
        const matchesGrade = option.dataset.grade === selectedGrade;
        option.hidden = !matchesGrade;
        option.disabled = !matchesGrade;
        if (matchesGrade) hasVisibleOption = true;
    });

    sectionSelect.disabled = !selectedGrade;
    sectionSelect.value = '';
    if (!hasVisibleOption) sectionSelect.value = '';
}

const studentGradeLevel = document.getElementById('studentGradeLevel');
if (studentGradeLevel) {
    studentGradeLevel.addEventListener('change', updateStudentSectionOptions);
}

function setStudentModalOpen(isOpen) {
    if (!studentModal) return;

    studentModal.classList.toggle('is-open', isOpen);
    studentModal.setAttribute('aria-hidden', String(!isOpen));
}

if (openStudentModalButton) {
    openStudentModalButton.addEventListener('click', function() {
        const form = studentModal ? studentModal.querySelector('form') : null;
        if (form) {
            form.reset();
            document.getElementById('studentAction').value = 'add_student';
            document.getElementById('currentStudentLrn').value = '';
            document.getElementById('studentModalTitle').textContent = 'Add New Student';
            document.getElementById('saveStudentButton').textContent = 'Save Student';
            updateStudentSectionOptions();
        }
        setStudentModalOpen(true);
    });
}

document.querySelectorAll('.edit-student-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        const form = studentModal ? studentModal.querySelector('form') : null;
        if (!form) return;

        form.reset();
        document.getElementById('studentAction').value = 'update_student';
        document.getElementById('currentStudentLrn').value = button.dataset.lrn || '';
        document.getElementById('studentLrn').value = button.dataset.lrn || '';
        document.getElementById('studentFirstName').value = button.dataset.firstName || '';
        document.getElementById('studentMiddleName').value = button.dataset.middleName || '';
        document.getElementById('studentLastName').value = button.dataset.lastName || '';
        document.getElementById('studentSuffix').value = button.dataset.suffix || '';
        const sectionId = button.dataset.sectionId || '';
        const sectionOption = document.querySelector('#studentSection option[value="' + sectionId + '"]');
        document.getElementById('studentGradeLevel').value = sectionOption ? sectionOption.dataset.grade : '';
        updateStudentSectionOptions();
        document.getElementById('studentSection').value = sectionId;
        document.getElementById('studentGender').value = button.dataset.gender || '';
        document.getElementById('studentModalTitle').textContent = 'Edit Student';
        document.getElementById('saveStudentButton').textContent = 'Save Changes';
        setStudentModalOpen(true);
    });
});

document.querySelectorAll('.deactivate-student-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        const studentName = form.querySelector('[data-student-name]').dataset.studentName;
        if (!window.confirm('Deactivate ' + studentName + '? Attendance history will be preserved.')) {
            event.preventDefault();
        }
    });
});

if (closeStudentModalButton) {
    closeStudentModalButton.addEventListener('click', function() {
        setStudentModalOpen(false);
    });
}

if (cancelStudentModalButton) {
    cancelStudentModalButton.addEventListener('click', function() {
        setStudentModalOpen(false);
    });
}

if (studentModal) {
    studentModal.addEventListener('click', function(event) {
        if (event.target === studentModal) setStudentModalOpen(false);
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') setStudentModalOpen(false);
    });
}

const studentSearch = document.getElementById('studentSearch');
const studentGradeFilter = document.getElementById('studentGradeFilter');
const studentSectionFilter = document.getElementById('studentSectionFilter');
const studentRecordCount = document.getElementById('studentRecordCount');
const studentTableBody = document.getElementById('studentTableBody');

function updateStudentSectionFilterOptions() {
    if (!studentGradeFilter || !studentSectionFilter) return;

    const selectedGrade = studentGradeFilter.value;
    let hasVisibleSection = false;

    Array.from(studentSectionFilter.options).forEach(function(option) {
        if (!option.value) return;
        const matchesGrade = !selectedGrade || option.dataset.grade === selectedGrade;
        option.hidden = !matchesGrade;
        option.disabled = !matchesGrade;
        if (matchesGrade) hasVisibleSection = true;
    });

    if (
        studentSectionFilter.value
        && (
            selectedGrade
            && studentSectionFilter.selectedOptions.length
            && studentSectionFilter.selectedOptions[0].dataset.grade !== selectedGrade
        )
    ) {
        studentSectionFilter.value = '';
    }

    studentSectionFilter.disabled = false;
}

function filterStudentRecords() {
    if (!studentTableBody) return;

    const searchText = (studentSearch?.value || '').trim().toLowerCase();
    const selectedGrade = studentGradeFilter?.value || '';
    const selectedSection = studentSectionFilter?.value || '';
    const rows = Array.from(studentTableBody.querySelectorAll('.student-record-row'));
    let visibleCount = 0;

    rows.forEach(function(row) {
        const matchesSearch = !searchText || (row.dataset.search || '').includes(searchText);
        const matchesGrade = !selectedGrade || row.dataset.grade === selectedGrade;
        const matchesSection = !selectedSection || row.dataset.sectionId === selectedSection;
        const isVisible = matchesSearch && matchesGrade && matchesSection;
        row.hidden = !isVisible;
        if (isVisible) visibleCount++;
    });

    if (studentRecordCount) {
        studentRecordCount.textContent = visibleCount + (visibleCount === 1 ? ' student' : ' students');
    }

    const emptyRow = document.getElementById('studentEmptyState');
    if (emptyRow) {
        if (rows.length > 0) {
            emptyRow.hidden = visibleCount !== 0;
        }
    }
}

if (studentSearch) {
    studentSearch.addEventListener('input', filterStudentRecords);
}

if (studentGradeFilter) {
    studentGradeFilter.addEventListener('change', function() {
        updateStudentSectionFilterOptions();
        filterStudentRecords();
    });
}

if (studentSectionFilter) {
    studentSectionFilter.addEventListener('change', filterStudentRecords);
}

updateStudentSectionFilterOptions();
filterStudentRecords();
