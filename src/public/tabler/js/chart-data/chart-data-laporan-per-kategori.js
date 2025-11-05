document.addEventListener("DOMContentLoaded", function () {
  window.ApexCharts &&
    new ApexCharts(document.getElementById("chart-data-laporan-per-kategori"), {
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
          data: [
            2, 9, 1, 7, 8, 3, 6, 5, 5, 4, 6, 4, 1, 9, 3, 6, 7, 5, 2, 8, 4, 9, 1, 2, 6, 7, 5, 1, 8, 3, 2, 9, 1, 7, 8, 3,
            6, 5, 5, 4, 6, 4, 1, 9, 3, 6, 7, 5, 2, 8, 4, 9, 1, 2, 6, 7, 5, 1, 8, 3,
          ],
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
          text: "Jumlah Laporan",
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
          "Topik 1",
          "Topik 2",
          "Topik 3",
          "Topik 4",
          "Topik 5",
          "Topik 6",
          "Topik 7",
          "Topik 8",
          "Topik 9",
          "Topik 10",
          "Topik 11",
          "Topik 12",
          "Topik 13",
          "Topik 14",
          "Topik 15",
          "Topik 16",
          "Topik 17",
          "Topik 18",
          "Topik 19",
          "Topik 20",
          "Topik 21",
          "Topik 22",
          "Topik 23",
          "Topik 24",
          "Topik 25",
          "Topik 26",
          "Topik 27",
          "Topik 28",
          "Topik 29",
          "Topik 30",
          "Topik 31",
          "Topik 32",
          "Topik 33",
          "Topik 34",
          "Topik 35",
          "Topik 36",
          "Topik 37",
          "Topik 38",
          "Topik 39",
          "Topik 40",
          "Topik 41",
          "Topik 42",
          "Topik 43",
          "Topik 44",
          "Topik 45",
          "Topik 46",
          "Topik 47",
          "Topik 48",
          "Topik 49",
          "Topik 50",
          "Topik 51",
          "Topik 52",
          "Topik 53",
          "Topik 54",
          "Topik 55",
          "Topik 56",
          "Topik 57",
          "Topik 58",
          "Topik 59",
          "Topik 60",
        ],
      },
      yaxis: {
        labels: {
          padding: 4,
        },
      },

      colors: ["color-mix(in srgb, transparent, var(--tblr-yellow) 80%)"],
      legend: {
        show: false,
      },
    }).render();
});
