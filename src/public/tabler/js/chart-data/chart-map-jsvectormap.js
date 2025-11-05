document.addEventListener("DOMContentLoaded", function () {
  const map = new jsVectorMap({
    selector: "#map-world",
    map: "world_merc",
    backgroundColor: "transparent",
    regionStyle: {
      initial: {
        fill: "var(--tblr-bg-surface-secondary)",
        stroke: "var(--tblr-border-color)",
        strokeWidth: 2,
      },
    },
    zoomOnScroll: false,
    zoomButtons: false,
    series: {
      regions: [
        {
          attribute: "fill",
          scale: {
            scale1: "color-mix(in srgb, transparent, var(--tblr-primary) 10%)",
            scale2: "color-mix(in srgb, transparent, var(--tblr-primary) 20%)",
            scale3: "color-mix(in srgb, transparent, var(--tblr-primary) 30%)",
            scale4: "color-mix(in srgb, transparent, var(--tblr-primary) 40%)",
            scale5: "color-mix(in srgb, transparent, var(--tblr-primary) 50%)",
            scale6: "color-mix(in srgb, transparent, var(--tblr-primary) 60%)",
            scale7: "color-mix(in srgb, transparent, var(--tblr-primary) 70%)",
            scale8: "color-mix(in srgb, transparent, var(--tblr-primary) 80%)",
            scale9: "color-mix(in srgb, transparent, var(--tblr-primary) 90%)",
            scale10:
              "color-mix(in srgb, transparent, var(--tblr-primary) 100%)",
          },
          values: {
            ID: "scale6",
          },
        },
      ],
    },
  });
  window.addEventListener("resize", () => {
    map.updateSize();
  });
});
