const teacherModal = document.getElementById('teacherModal');
const openTeacherModalButton = document.getElementById('openTeacherModal');
const closeTeacherModalButton = document.getElementById('closeTeacherModal');
const cancelTeacherModalButton = document.getElementById('cancelTeacherModal');

function openTeacherModal() {
    teacherModal.classList.add('is-open');
    teacherModal.setAttribute('aria-hidden', 'false');
}

function closeTeacherModal() {
    teacherModal.classList.remove('is-open');
    teacherModal.setAttribute('aria-hidden', 'true');
}

openTeacherModalButton.addEventListener('click', openTeacherModal);
closeTeacherModalButton.addEventListener('click', closeTeacherModal);
cancelTeacherModalButton.addEventListener('click', closeTeacherModal);