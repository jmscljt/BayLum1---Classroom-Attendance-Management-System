document.addEventListener("DOMContentLoaded", function() {
    const addModal = document.getElementById("addStudentModal");
    const editModal = document.getElementById("editStudentModal");
    const removeModal = document.getElementById("removeStudentModal");
    const inactiveStudentsModal = document.getElementById("inactiveStudentsModal");
    const directFingerprintEnrollmentModal = document.getElementById("directFingerprintEnrollmentModal");
    const addBtn = document.querySelector(".add-student-btn");
    const viewInactiveBtn = document.getElementById("viewInactiveStudentsBtn");

    if (addBtn && addModal) {
        addBtn.addEventListener("click", function(event) {
            event.preventDefault();
            addModal.style.display = "flex";
        });
    }

    if (viewInactiveBtn && inactiveStudentsModal) {
        viewInactiveBtn.addEventListener("click", function() {
            inactiveStudentsModal.style.display = "flex";
            inactiveStudentsModal.setAttribute("aria-hidden", "false");
        });
    }

    function bindModal(modal) {
        if (!modal) return;

        const closeModal = function() {
            modal.style.display = "none";
            modal.setAttribute("aria-hidden", "true");
        };
        const closeBtn = modal.querySelector(".close-modal");
        const cancelBtn = modal.querySelector(".cancel-btn");

        if (closeBtn) closeBtn.addEventListener("click", closeModal);
        if (cancelBtn) cancelBtn.addEventListener("click", closeModal);
        modal.addEventListener("click", function(event) {
            if (event.target === modal) closeModal();
        });
    }

    bindModal(addModal);
    bindModal(editModal);
    bindModal(removeModal);
    bindModal(inactiveStudentsModal);
    bindModal(directFingerprintEnrollmentModal);

    if (directFingerprintEnrollmentModal && directFingerprintEnrollmentModal.dataset.open === "1") {
        directFingerprintEnrollmentModal.style.display = "flex";
        directFingerprintEnrollmentModal.setAttribute("aria-hidden", "false");
    }

    if (removeModal) {
        const removeStudentId = document.getElementById("remove_student_id");
        document.querySelectorAll(".btn-remove").forEach(function(button) {
            button.addEventListener("click", function() {
                removeStudentId.value = button.dataset.studentId || "";
                removeModal.style.display = "flex";
                removeModal.setAttribute("aria-hidden", "false");
            });
        });
    }

    if (!editModal) return;

    const currentStudentId = document.getElementById("current_student_id");
    const lrnInput = document.getElementById("edit_lrn");
    const firstNameInput = document.getElementById("edit_first_name");
    const middleNameInput = document.getElementById("edit_middle_name");
    const lastNameInput = document.getElementById("edit_last_name");
    const suffixInput = document.getElementById("edit_suffix");
    const clearFingerprintInput = document.getElementById("clear_fingerprint_id");
    const fingerprintStatus = document.getElementById("edit_fingerprint_status");
    const genderInput = document.getElementById("edit_gender");

    document.querySelectorAll(".btn-edit").forEach(function(button) {
        button.addEventListener("click", function() {
            const studentId = button.dataset.studentId || "";
            currentStudentId.value = studentId;
            lrnInput.value = studentId;
            firstNameInput.value = button.dataset.firstName || "";
            middleNameInput.value = button.dataset.middleName || "";
            lastNameInput.value = button.dataset.lastName || "";
            suffixInput.value = button.dataset.suffix || "";
            clearFingerprintInput.checked = false;
            const fingerprintId = button.dataset.fingerprintId || "";
            fingerprintStatus.textContent = fingerprintId
                ? `Registered fingerprint ID: ${fingerprintId}`
                : "No fingerprint is currently registered.";
            genderInput.value = button.dataset.gender || "";
            editModal.style.display = "flex";
            editModal.setAttribute("aria-hidden", "false");
            firstNameInput.focus();
        });
    });
});
