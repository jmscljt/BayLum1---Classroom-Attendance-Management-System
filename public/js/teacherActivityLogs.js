const dateFilter = document.getElementById('activityLogDateFilter');
const actionFilter = document.getElementById('activityLogActionFilter');
const tableBody = document.getElementById('activityLogsTableBody');
const count = document.getElementById('activityLogsCount');
const emptyState = document.getElementById('activityLogsEmptyState');
const rows = Array.from(tableBody.querySelectorAll('tr:not(#activityLogsEmptyState)'));

function filterTeacherLogs() {
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
        : 'No teacher activity records available yet.';
}

[dateFilter, actionFilter].forEach(function(filter) {
    filter.addEventListener('change', filterTeacherLogs);
});

document.getElementById('clearActivityLogFilters').addEventListener('click', function() {
    dateFilter.value = '';
    actionFilter.value = '';
    filterTeacherLogs();
});

filterTeacherLogs();
