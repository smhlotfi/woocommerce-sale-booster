jQuery(document).ready(function ($) {
    $.ajax({
        url: sb_sales_data.ajax_url,
        method: 'POST',
        data: { action: 'sb_get_sales_data' },
        success: function (response) {
            if (!response || response.length === 0) {
                console.log("No sales data found.");
                return;
            }

            let labels = [];
            let completedOrders = [];
            let cancelledOrders = [];

            response.forEach(row => {
                labels.push(row.order_date);
                completedOrders.push(row.completed_orders);
                cancelledOrders.push(row.cancelled_orders);
            });

            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
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
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Date' } },
                        y: { title: { display: true, text: 'Orders' }, beginAtZero: true }
                    }
                }
            });
        },
        error: function () {
            console.log("Error loading sales data.");
        }
    });
});
