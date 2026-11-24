const canvas = document.getElementById('monthlyAttendanceChart');
if (canvas) {
    const emptyMessage = document.getElementById('analyticsEmpty');
    const analytics = window.analyticsData;
    const chartUnavailable = typeof Chart === 'undefined';

    if (chartUnavailable || !analytics || !analytics.overall || analytics.overall.schoolDays === 0) {
        canvas.parentElement.hidden = true;
        emptyMessage.textContent = chartUnavailable
            ? 'The analytics chart could not be loaded. Please refresh the page.'
            : 'No attendance data recorded yet for the last few months.';
        emptyMessage.hidden = false;
    } else {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: analytics.months,
                datasets: [
                    { label: 'Present - Male', data: analytics.presentMale, backgroundColor: '#3b82f6' },
                    { label: 'Present - Female', data: analytics.presentFemale, backgroundColor: '#ec4899' },
                    { label: 'Absent - Male', data: analytics.absentMale, backgroundColor: '#93c5fd' },
                    { label: 'Absent - Female', data: analytics.absentFemale, backgroundColor: '#f9a8d4' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Avg. students per school day' }
                    }
                }
            }
        });
    }
}
