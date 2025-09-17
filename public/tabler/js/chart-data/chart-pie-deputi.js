document.addEventListener("DOMContentLoaded", function () {
  const chartDataDeputi = {
    deputi_1: [
      { label: "Selesai = 2", value: 2, whatsapp: 0, tatap_muka: 2 },
      { label: "Kelengkapan = 0", value: 0, whatsapp: 0, tatap_muka: 0 },
      { label: "Tindak Lanjut K/L = 3", value: 3, whatsapp: 1, tatap_muka: 2 },
      { label: "Verifikasi = 33", value: 33, whatsapp: 4, tatap_muka: 29 },
    ],
    deputi_2: [
      { label: "Selesai = 3", value: 3, whatsapp: 1, tatap_muka: 2 },
      { label: "Kelengkapan = 0", value: 0, whatsapp: 0, tatap_muka: 0 },
      { label: "Tindak Lanjut K/L = 0", value: 0, whatsapp: 0, tatap_muka: 0 },
      { label: "Verifikasi = 46", value: 46, whatsapp: 26, tatap_muka: 20 },
    ],
    deputi_3: [
      { label: "Selesai = 2", value: 2, whatsapp: 1, tatap_muka: 1 },
      { label: "Kelengkapan = 0", value: 0, whatsapp: 0, tatap_muka: 0 },
      { label: "Tindak Lanjut K/L = 0", value: 0, whatsapp: 0, tatap_muka: 0 },
      { label: "Verifikasi = 33", value: 33, whatsapp: 13, tatap_muka: 20 },
    ],
    deputi_4: [
      { label: "Selesai = 1", value: 1, whatsapp: 0, tatap_muka: 1 },
      { label: "Kelengkapan = 0", value: 0, whatsapp: 0, tatap_muka: 0 },
      { label: "Tindak Lanjut K/L = 4", value: 4, whatsapp: 3, tatap_muka: 1 },
      { label: "Verifikasi = 156", value: 156, whatsapp: 103, tatap_muka: 52 },
    ],
  };

  function createPieChart(canvasId, data, title) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
      console.warn(`Canvas dengan ID "${canvasId}" tidak ditemukan.`);
      return;
    }

    if (!data || data.length === 0) {
      console.warn(`Data kosong untuk "${canvasId}".`);
      return;
    }

    const labels = data.map((item) => item.label);
    const values = data.map((item) => item.value);

    window.ApexCharts &&
      new ApexCharts(canvas, {
        chart: {
          type: "donut",
          fontFamily: "inherit",
          height: "auto",
          sparkline: {
            enabled: true,
          },
        },
        title: {
          text: title,
          margin: 20,
          offsetY: -4,
          align: "center",
          style: {
            fontSize: "16px",
            fontWeight: "bold",
            color: "#263238",
          },
        },
        dataLabels: {
          enabled: false,
        },
        series: values,
        labels: labels,
        tooltip: {
          theme: "dark",
          fillSeriesColor: false,
          custom: function ({ series, seriesIndex, dataPointIndex, w }) {
            let label = data[seriesIndex].label;
            let whatsappCount = data[seriesIndex].whatsapp ?? 0;
            let tatapMukaCount = data[seriesIndex].tatap_muka ?? 0;

            return [
              `${label}\n`,
              `📲 WhatsApp: ${whatsappCount}\n`,
              `🤝 Tatap Muka: ${tatapMukaCount}\n`,
            ];
          },
        },
        grid: {
          strokeDashArray: 4,
        },
        colors: [
          "color-mix(in srgb, transparent, var(--tblr-primary) 70%)",
          "color-mix(in srgb, transparent, var(--tblr-primary) 40%)",
          "color-mix(in srgb, transparent, var(--tblr-gray-300) 100%)",
          "color-mix(in srgb, transparent, var(--tblr-primary) 100%)",
        ],
        legend: {
          show: true,
          position: "bottom",
          height: "auto",
          markers: {
            width: 10,
            height: 10,
            radius: 100,
          },
          itemMargin: {
            horizontal: 4,
            vertical: 4,
          },
        },
      }).render();
  }

  // **Inisialisasi pie chart untuk masing-masing deputi (Admin & Superadmin)**
  ["deputi_1", "deputi_2", "deputi_3", "deputi_4"].forEach((deputi, index) => {
    createPieChart(
      `chart-pie-${deputi}`,
      chartDataDeputi[deputi],
      `Status Laporan Deputi ${index + 1}`
    );
  });
});
