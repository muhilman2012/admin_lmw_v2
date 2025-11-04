document.addEventListener("DOMContentLoaded", function () {
    // Ambil data yang disuntikkan dari PHP Blade
    const dataLaporan = window.reportChartData;
    const chartElement = document.getElementById("chart-data-laporan");

    // 1. Cek Data dan Elemen
    if (!dataLaporan || !chartElement || !window.ApexCharts || dataLaporan.series.length === 0) {
        if (chartElement) {
            chartElement.innerHTML = '<div class="text-center text-muted p-5">Tidak ada laporan untuk rentang waktu ini.</div>';
        }
        return;
    }

    const chartOptions = {
        // MENGGUNAKAN DATA DINAMIS DARI CONTROLLER
        series: dataLaporan.series, 
        chart: {
            type: "bar",
            fontFamily: "inherit",
            height: 350, // Disesuaikan sedikit
            parentHeightOffset: 0,
            animations: {
                enabled: false,
            },
            stacked: true,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: false,
                    reset: true
                }
            },
            zoom: {
                enabled: true,
            },
        },
        plotOptions: {
            bar: {
                columnWidth: "70%",
                endingShape: 'rounded'
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        tooltip: {
            theme: "dark",
            y: {
                formatter: function (val) {
                    return val + " laporan";
                }
            }
        },
        grid: {
            padding: {
                top: -20,
                right: 0,
                left: -4,
                bottom: -4,
            },
            strokeDashArray: 4,
            xaxis: {
                lines: {
                    show: true,
                },
            },
        },
        // MENGGUNAKAN LABELS DINAMIS DARI CONTROLLER
        xaxis: {
            categories: dataLaporan.labels, // Data d/M atau d/m/Y dikirim sebagai kategori
            labels: {
                padding: 0,
            },
            tooltip: {
                enabled: false,
            },
            axisBorder: {
                show: false,
            },
            tickPlacement: 'on',
            type: "category", // Diubah dari 'datetime' ke 'category' untuk data string label
        },
        yaxis: {
            labels: {
                padding: 4,
            },
            tickAmount: 5 // Menjaga jumlah ticks tetap
        },
        // Warna diambil dari series data (jika ada) atau fallback
        colors: [
             '#20c997', // green (Whatsapp)
             '#4676fe', // primary (Tatap Muka)
             '#ffc330', // yellow (Surat)
        ],
        legend: {
            show: true,
            position: 'top',
        },
    };

    new ApexCharts(chartElement, chartOptions).render();
});