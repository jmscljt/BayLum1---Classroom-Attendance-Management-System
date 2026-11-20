const sectionModal = document.getElementById('sectionModal');
const openSectionModalButton = document.getElementById('openSectionModal');
const closeSectionModalButton = document.getElementById('closeSectionModal');
const cancelSectionModalButton = document.getElementById('cancelSectionModal');
const sectionForm = sectionModal ? sectionModal.querySelector('form') : null;

let sectionModalReturnFocus = null;

function openSectionModal() {
    if (!sectionModal || !sectionForm) return;

    sectionModal.classList.add('is-open');
    sectionModal.setAttribute('aria-hidden', 'false');
    document.getElementById('grade_level').focus();
}

function closeSectionModal() {
    if (!sectionModal) return;

    sectionModal.classList.remove('is-open');
    sectionModal.setAttribute('aria-hidden', 'true');
    if (sectionModalReturnFocus) sectionModalReturnFocus.focus();
}

if (sectionModal && openSectionModalButton && sectionForm) {
    openSectionModalButton.addEventListener('click', () => {
        sectionForm.reset();
        sectionForm.querySelector('[name="action"]').value = 'add_section';
        document.getElementById('section_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add New Section';
        document.getElementById('saveSectionButton').textContent = 'Save Section';
        document.getElementById('noAdviserOption').disabled = false;
        sectionModalReturnFocus = openSectionModalButton;
        openSectionModal();
    });

    document.querySelectorAll('.edit-section-btn').forEach(button => {
        button.addEventListener('click', () => {
            sectionForm.reset();
            sectionForm.querySelector('[name="action"]').value = 'update_section';
            document.getElementById('section_id').value = button.dataset.sectionId;
            document.getElementById('grade_level').value = button.dataset.gradeLevel;
            document.getElementById('section_name').value = button.dataset.sectionName;
            document.getElementById('adviser_id').value = button.dataset.adviserId;
            document.getElementById('noAdviserOption').disabled = button.dataset.hasAdviser === 'true';
            document.getElementById('modalTitle').textContent = 'Edit Grade & Section';
            document.getElementById('saveSectionButton').textContent = 'Save Changes';
            sectionModalReturnFocus = button;
            openSectionModal();
        });
    });

    closeSectionModalButton.addEventListener('click', closeSectionModal);
    cancelSectionModalButton.addEventListener('click', closeSectionModal);
    sectionModal.addEventListener('click', event => {
        if (event.target === sectionModal) closeSectionModal();
    });
}

document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    if (sectionModal && sectionModal.classList.contains('is-open')) {
        closeSectionModal();
    }
});

document.querySelectorAll('.remove-section-form').forEach(form => {
    form.addEventListener('submit', event => {
        const sectionName = form.querySelector('[data-section-name]').dataset.sectionName;
        if (!window.confirm(`Remove ${sectionName}? Sections with students or an assigned adviser cannot be removed.`)) {
            event.preventDefault();
        }
    });
});
