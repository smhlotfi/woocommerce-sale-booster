jQuery(document).ready(function($) {
    $.ajax({
        url: sb_sales_data.ajax_url,
        method: 'POST',
        data: { action: 'sb_get_sales_data' },
        success: function(response) {
            let labels = response.map(item => item.order_date);
            let data = response.map(item => item.order_count);

            let ctx = document.getElementById('salesTrendChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Completed Orders',
                        data: data,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { title: { display: true, text: 'Date' } },
                        y: { title: { display: true, text: 'Orders' }, beginAtZero: true }
                    }
                }
            });
        }
    });
});
