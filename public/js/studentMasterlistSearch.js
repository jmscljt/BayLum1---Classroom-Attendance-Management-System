(function() {
    const searchInput = document.getElementById('studentMasterlistSearch');
    const tableBody = document.getElementById('studentMasterlistTableBody');
    const searchCount = document.getElementById('studentSearchCount');
    const emptyState = document.getElementById('studentSearchEmptyState');
    const noDataState = document.getElementById('studentMasterlistNoData');

    if (!searchInput || !tableBody) return;

    function filterStudents() {
        const query = searchInput.value.trim().toLocaleLowerCase();
        const rows = Array.from(tableBody.querySelectorAll('.student-masterlist-row'));
        let visibleCount = 0;

        rows.forEach(function(row) {
            const matches = !query || row.textContent.toLocaleLowerCase().includes(query);
            row.hidden = !matches;
            if (matches) visibleCount++;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }

        if (noDataState) {
            noDataState.hidden = Boolean(query);
        }

        if (searchCount) {
            searchCount.textContent = query
                ? visibleCount + (visibleCount === 1 ? ' result' : ' results')
                : '';
        }
    }

    searchInput.addEventListener('input', filterStudents);
})();
