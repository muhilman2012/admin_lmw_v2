document.addEventListener("DOMContentLoaded", function () {
  window.ApexCharts &&
    new ApexCharts(document.getElementById("chart-data-laporan-per-judul"), {
      chart: {
        type: "bar",
        fontFamily: "inherit",
        height: "100%",
        parentHeightOffset: 0,
        animations: {
          enabled: false,
        },
        zoom: {
          enabled: true,
        },
      },
      plotOptions: {
        bar: {
          columnWidth: "50%",
        },
      },
      dataLabels: {
        enabled: false,
      },
      series: [
        {
          name: "Jumlah Laporan",
          data: [2, 9, 1, 7, 8, 3, 6, 5, 5, 4, 6, 4, 1, 9, 3, 6, 7, 5, 2, 8],
        },
      ],
      tooltip: {
        theme: "dark",
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
      yaxis: {
        title: {
          text: "Jumlah Pengaduan",
        },
      },
      xaxis: {
        labels: {
          padding: 0,
        },
        tooltip: {
          enabled: false,
        },
        axisBorder: {
          show: false,
        },
        type: "category",
        tickPlacement: "on",
        categories: [
          "Judul 1",
          "Judul 2",
          "Judul 3",
          "Judul 4",
          "Judul 5",
          "Judul 6",
          "Judul 7",
          "Judul 8",
          "Judul 9",
          "Judul 10",
          "Judul 11",
          "Judul 12",
          "Judul 13",
          "Judul 14",
          "Judul 15",
          "Judul 16",
          "Judul 17",
          "Judul 18",
          "Judul 19",
          "Judul 20",
        ],
      },
      yaxis: {
        labels: {
          padding: 4,
        },
      },

      colors: ["color-mix(in srgb, transparent, var(--tblr-teal) 80%)"],
      legend: {
        show: false,
      },
    }).render();
});
