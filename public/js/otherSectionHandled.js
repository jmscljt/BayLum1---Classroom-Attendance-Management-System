document.addEventListener('DOMContentLoaded', function () {
    const recordModal = document.getElementById('sectionRecordsModal');
    const editModal = document.getElementById('editAssignmentModal');
    const recordRows = document.getElementById('sectionLogRows');
    const recordTitle = document.getElementById('sectionRecordsTitle');
    const recordMessage = document.getElementById('sectionRecordMessage');
    const adviserLabel = document.getElementById('sectionAdviser');

    function showModal(modal) {
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }

    function filterSections(gradeSelect, sectionSelect) {
        const selectedGrade = gradeSelect.value;
        sectionSelect.disabled = selectedGrade === '';
        Array.from(sectionSelect.options).forEach(function (option, index) {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            option.hidden = selectedGrade !== '' && option.dataset.grade !== selectedGrade;
        });

        const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
        if (selectedOption && selectedOption.hidden) {
            sectionSelect.value = '';
        }
    }

    document.querySelectorAll('[data-grade-select]').forEach(function (gradeSelect) {
        const sectionSelect = gradeSelect.parentElement.querySelector('[data-section-select]');
        if (!sectionSelect) {
            return;
        }
        gradeSelect.addEventListener('change', function () {
            filterSections(gradeSelect, sectionSelect);
        });
        filterSections(gradeSelect, sectionSelect);
    });

    document.querySelectorAll('.btn-view-record').forEach(function (button) {
        button.addEventListener('click', async function () {
            recordTitle.textContent = 'Students - ' + button.dataset.label;
            adviserLabel.textContent = '';
            recordMessage.textContent = 'Loading student records...';
            recordRows.replaceChildren();
            showModal(recordModal);

            try {
                const response = await fetch(
                    'index.php?page=otherSectionHandled&record=' + encodeURIComponent(button.dataset.assignmentId),
                    { headers: { Accept: 'application/json' } }
                );
                const record = await response.json();
                if (!response.ok) {
                    throw new Error(record.error || 'Unable to load this student record.');
                }

                adviserLabel.textContent = 'Class Adviser: ' + record.section.class_adviser;
                recordMessage.textContent = record.students.length
                    ? record.students.length + ' student(s)'
                    : 'No active students are listed for this section.';

                record.students.forEach(function (student) {
                    const row = document.createElement('tr');
                    const name = [student.first_name, student.middle_name, student.last_name]
                        .filter(Boolean)
                        .join(' ');
                    [student.lrn, name, student.gender].forEach(function (value) {
                        const cell = document.createElement('td');
                        cell.textContent = value || '';
                        row.appendChild(cell);
                    });
                    recordRows.appendChild(row);
                });
            } catch (error) {
                recordMessage.textContent = error.message;
            }
        });
    });

    document.querySelectorAll('.btn-edit-assignment').forEach(function (button) {
        button.addEventListener('click', function () {
            const gradeSelect = document.getElementById('editGradeSelect');
            const sectionSelect = document.getElementById('editSectionSelect');
            gradeSelect.value = button.dataset.grade;
            filterSections(gradeSelect, sectionSelect);
            sectionSelect.value = button.dataset.sectionId;
            document.getElementById('editAssignmentId').value = button.dataset.assignmentId;
            document.getElementById('editSubjectSelect').value = button.dataset.subject;
            showModal(editModal);
        });
    });

    document.querySelectorAll('.remove-assignment-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm('Remove this assigned section?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(button.closest('.attendance-modal-overlay'));
        });
    });

    [recordModal, editModal].forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });
});
