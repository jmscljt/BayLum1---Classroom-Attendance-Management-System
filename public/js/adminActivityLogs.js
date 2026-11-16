const dateFilter = document.getElementById('adminLogDateFilter');
const actionFilter = document.getElementById('adminLogActionFilter');
const tableBody = document.getElementById('adminLogsTableBody');
const count = document.getElementById('adminLogsCount');
const emptyState = document.getElementById('adminLogsEmptyState');
const rows = Array.from(tableBody.querySelectorAll('tr:not(#adminLogsEmptyState)'));

function filterLogs() {
    const visibleRows = rows.filter(function(row) {
        const matchesDate = !dateFilter.value || row.cells[0].textContent.indexOf(dateFilter.value) === 0;
        const matchesAction = !actionFilter.value || row.cells[2].textContent.trim().toLowerCase() === actionFilter.value;
        row.hidden = !(matchesDate && matchesAction);
        return !row.hidden;
    });

    count.textContent = visibleRows.length + (visibleRows.length === 1 ? ' record' : ' records');
    emptyState.hidden = visibleRows.length > 0;
    emptyState.querySelector('td').textContent = rows.length
        ? 'No records match the selected filters.'
        : 'No admin activity records available yet.';
}

[dateFilter, actionFilter].forEach(function(filter) {
    filter.addEventListener('change', filterLogs);
});

document.getElementById('clearAdminLogFilters').addEventListener('click', function() {
    dateFilter.value = '';
    actionFilter.value = '';
    filterLogs();
});

filterLogs();
