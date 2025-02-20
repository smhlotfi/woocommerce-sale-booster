jQuery(document).ready(function ($) {
    $.ajax({
        url: sb_sales_data.ajax_url,
        method: 'POST',
        data: { action: 'sb_get_sales_data' },
        success: function (response) {
            // console.log("Raw Response:", response); // Debugging output
            if (!response || response.daily_data.length === 0) {
                console.log("No sales data found.");
                return;
            }

            // Extract line chart data
            let labels = [];
            let completedOrders = [];
            let cancelledOrders = [];

            response.daily_data.forEach(row => {
                labels.push(row.order_date);
                completedOrders.push(row.completed_orders);
                cancelledOrders.push(row.cancelled_orders);
            });

            // Draw Line Chart
            const ctxLine = document.getElementById('salesTrendChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Completed Orders',
                            data: completedOrders,
                            borderColor: 'green',
                            backgroundColor: 'rgba(0, 128, 0, 0.2)',
                            borderWidth: 2,
                            tension: 0.3,
                        },
                        {
                            label: 'Canceled Orders',
                            data: cancelledOrders,
                            borderColor: 'red',
                            backgroundColor: 'rgba(255, 0, 0, 0.2)',
                            borderWidth: 2,
                            tension: 0.3,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Date' } },
                        y: { title: { display: true, text: 'Orders' }, beginAtZero: true }
                    }
                }
            });

            // Extract pie chart data
            let totalCompleted = response.total_data.total_completed || 0;
            let totalCancelled = response.total_data.total_cancelled || 0;

            // Draw Pie Chart
            const ctxPie = document.getElementById('salesPieChart').getContext('2d');
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: ['Completed Orders', 'Canceled Orders'],
                    datasets: [{
                        data: [totalCompleted, totalCancelled],
                        backgroundColor: ['green', 'red'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true, position: 'bottom' }
                    }
                }
            });
        },
        error: function () {
            console.log("Error loading sales data.");
        }
    });
});
