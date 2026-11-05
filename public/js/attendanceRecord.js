function openAttendanceModal() {
    document.getElementById('attendanceModal').style.display = 'flex';
}

function closeAttendanceModal() {
    document.getElementById('attendanceModal').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('attendanceModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}