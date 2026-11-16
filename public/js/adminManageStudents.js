const studentModal = document.getElementById('studentModal');
const openStudentModalButton = document.getElementById('openStudentModal');
const closeStudentModalButton = document.getElementById('closeStudentModal');
const cancelStudentModalButton = document.getElementById('cancelStudentModal');

function setStudentModalOpen(isOpen) {
    if (!studentModal) return;

    studentModal.classList.toggle('is-open', isOpen);
    studentModal.setAttribute('aria-hidden', String(!isOpen));
}

if (openStudentModalButton) {
    openStudentModalButton.addEventListener('click', function() {
        setStudentModalOpen(true);
    });
}

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
