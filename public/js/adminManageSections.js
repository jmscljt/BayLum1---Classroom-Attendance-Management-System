const sectionModal = document.getElementById('sectionModal');
const openSectionModalButton = document.getElementById('openSectionModal');
const closeSectionModalButton = document.getElementById('closeSectionModal');
const cancelSectionModalButton = document.getElementById('cancelSectionModal');

function openSectionModal() {
    sectionModal.classList.add('is-open');
    sectionModal.setAttribute('aria-hidden', 'false');
}

function closeSectionModal() {
    sectionModal.classList.remove('is-open');
    sectionModal.setAttribute('aria-hidden', 'true');
}

openSectionModalButton.addEventListener('click', openSectionModal);
closeSectionModalButton.addEventListener('click', closeSectionModal);
cancelSectionModalButton.addEventListener('click', closeSectionModal);
