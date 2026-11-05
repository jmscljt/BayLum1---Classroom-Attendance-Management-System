// Buksan ang Absences Modal
function openAbsencesModal() {
    document.getElementById('absencesModal').style.display = 'flex';
}

// Isara ang Absences Modal
function closeAbsencesModal() {
    document.getElementById('absencesModal').style.display = 'none';
}

// Ipakita o itago ang excuse section depende sa pinili sa dropdown
function toggleExcuseReason(selectElement) {
    const excuseGroup = document.getElementById('excuseReasonGroup');
    if (selectElement.value === 'EXCUSE') {
        excuseGroup.style.display = 'block';
    } else {
        excuseGroup.style.display = 'none';
    }
}

// Isara kapag pinindot ang labas ng modal box
window.addEventListener('click', function(event) {
    const modal = document.getElementById('absencesModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});