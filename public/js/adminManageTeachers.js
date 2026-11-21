const teacherModal = document.getElementById('teacherModal');
const closeTeacherModalButton = document.getElementById('closeTeacherModal');
const cancelTeacherModalButton = document.getElementById('cancelTeacherModal');
const teacherForm = teacherModal ? teacherModal.querySelector('form') : null;

let teacherModalReturnFocus = null;

function openTeacherModal() {
    if (!teacherModal) return;
    teacherModal.classList.add('is-open');
    teacherModal.setAttribute('aria-hidden', 'false');
    document.getElementById('teacher_first_name').focus();
}

function closeTeacherModal() {
    if (!teacherModal) return;
    teacherModal.classList.remove('is-open');
    teacherModal.setAttribute('aria-hidden', 'true');
    if (teacherModalReturnFocus) teacherModalReturnFocus.focus();
}

if (teacherModal && teacherForm) {
    document.querySelectorAll('.edit-teacher-btn').forEach(button => {
        button.addEventListener('click', () => {
            teacherForm.reset();
            teacherForm.querySelector('[name="action"]').value = 'update_teacher';
            document.getElementById('teacher_id').value = button.dataset.teacherId;
            document.getElementById('teacher_first_name').value = button.dataset.firstName;
            document.getElementById('teacher_last_name').value = button.dataset.lastName;
            document.getElementById('teacher_email').value = button.dataset.email;
            document.getElementById('teacherModalTitle').textContent = 'Edit Teacher Account';
            document.getElementById('saveTeacherButton').textContent = 'Save Changes';
            teacherModalReturnFocus = button;
            openTeacherModal();
        });
    });

    closeTeacherModalButton.addEventListener('click', closeTeacherModal);
    cancelTeacherModalButton.addEventListener('click', closeTeacherModal);
    teacherModal.addEventListener('click', event => {
        if (event.target === teacherModal) closeTeacherModal();
    });
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && teacherModal && teacherModal.classList.contains('is-open')) {
        closeTeacherModal();
    }
});

const searchInput = document.querySelector('.teacher-search-input');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        document.querySelectorAll('.teacher-row').forEach(row => {
            row.hidden = query !== '' && !row.dataset.search.includes(query);
        });
    });
}

document.querySelectorAll('.remove-teacher-form').forEach(form => {
    form.addEventListener('submit', event => {
        const teacherName = form.querySelector('[data-teacher-name]').dataset.teacherName;
        if (!window.confirm(`Remove the teacher account for ${teacherName}?`)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('.teacher-status-form').forEach(form => {
    form.addEventListener('submit', event => {
        const button = form.querySelector('[data-teacher-name]');
        if (
            button.dataset.nextStatus === 'inactive'
            && !window.confirm(`Deactivate ${button.dataset.teacherName}? They will no longer be able to sign in.`)
        ) {
            event.preventDefault();
        }
    });
});