const deviceModal = document.getElementById('deviceModal');

function setDeviceModalOpen(isOpen) {
    deviceModal.classList.toggle('is-open', isOpen);
    deviceModal.setAttribute('aria-hidden', String(!isOpen));
}

document.getElementById('openDeviceModal').addEventListener('click', function() {
    setDeviceModalOpen(true);
});

document.getElementById('closeDeviceModal').addEventListener('click', function() {
    setDeviceModalOpen(false);
});

document.getElementById('cancelDeviceModal').addEventListener('click', function() {
    setDeviceModalOpen(false);
});

deviceModal.addEventListener('click', function(event) {
    if (event.target === deviceModal) setDeviceModalOpen(false);
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') setDeviceModalOpen(false);
});
