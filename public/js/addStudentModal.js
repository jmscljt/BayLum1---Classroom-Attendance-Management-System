document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("addStudentModal");
    const addBtn = document.querySelector(".add-student-btn");
    const closeBtn = document.querySelector(".close-modal");
    const cancelBtn = document.querySelector(".cancel-btn");

    if (addBtn) {
        addBtn.addEventListener("click", function(e) {
            e.preventDefault();
            modal.style.display = "flex";
        });
    }

    function closeModal() {
        modal.style.display = "none";
    }

    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    if (cancelBtn) cancelBtn.addEventListener("click", closeModal);

    window.addEventListener("click", function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
});