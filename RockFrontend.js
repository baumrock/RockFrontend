"use strict";

/**
 * RockFrontend JavaScript Helper
 *
 * To include this file into your frontend add it to the head scripts:
 * $rockfrontend->add('/site/modules/RockFrontend/RockFrontend.js', 'defer')
 */

(function () {
  let RF = RockFrontend;

  /**
   * toggle the "hidden" attribute of another element
   * Usage:
   * <a href=# rf-toggle='#foo'>toggle foo</a>
   * <div id='foo'>hello world</div>
   */
  document.addEventListener("click", function (e) {
    let toggle = e.target.closest("[rf-toggle]");
    if (!toggle) return;
    e.preventDefault();
    let selector = toggle.getAttribute("rf-toggle");
    let target = document.querySelector(selector);
    if (target.hasAttribute("hidden")) target.removeAttribute("hidden");
    else target.setAttribute("hidden", "");
  });

  /**
   * RockFrontend grow variable feature
   * The grow factor will be written to the root element's --grow css variable
   * on window resize and have a value between 0 and 1. You can use this variable
   * to grow fonts or section paddings based on the current viewport width.
   */
  let setGrow = function () {
    let root = document.documentElement;
    let w = window.innerWidth; // viewport width
    let min = RF.growMin;
    let max = RF.growMax;
    let grow = 0;
    if (w <= min) grow = 0;
    else if (w >= max) grow = 1;
    else grow = ((w - min) / (max - min)).toFixed(3);
    root.style.setProperty("--rf-grow", grow);
  };
  window.addEventListener("resize", setGrow);
  setGrow();
})();
