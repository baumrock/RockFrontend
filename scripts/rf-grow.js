// Adds the --rf-grow CSS variable for using fluid font sizes and paddings/margins
(() => {
  /**
   * RockFrontend Grow Feature
   *
   * The grow factor will be written to the root element's --grow css variable
   * on window resize and have a value between 0 and 1. You can use this variable
   * to grow fonts or section paddings based on the current viewport width.
   *
   * Usage:
   * font-size: rfGrow(10px, 40px);
   * font-size: calc(10px + 30px * var(--rf-grow));
   *
   * Set custom breakpoints:
   * $rockfrontend->js('growMin', 600);
   * $rockfrontend->js('growMax', 800);
   */
  let RF = RockFrontend;
  let root = document.documentElement;
  let ro = new ResizeObserver(() => {
    let w = window.innerWidth; // viewport width
    let min = RF.growMin || 400;
    let max = RF.growMax || 1440;
    let grow = 0;
    if (w <= min) grow = 0;
    else if (w >= max) grow = 1;
    else grow = ((w - min) / (max - min)).toFixed(3);
    root.style.setProperty("--rf-grow", grow);
  });
  ro.observe(document.querySelector("html"));
  setTimeout(() => {
    let val = root.style.getPropertyValue("--rf-grow");
    if (val) return;
    root.style.setProperty("--rf-grow", 0.5);
  }, 0);
})();
