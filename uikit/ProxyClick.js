"use strict";

/**
 * Proxy Clicks Feature
 *
 * Usage:
 * <a href=# rf-proxy-click=".clicked-element-selector">...</a>
 * <a href=# rf-proxy-click=".parent-selector;.clicked-element-selector">...</a>
 */

(function () {
  let util = UIkit.util;

  util.on(document, "click", (e) => {
    let el = e.target.closest("[rf-proxy-click]");
    if (!el) return;
    e.preventDefault();
    let selector = util.attr(el, "rf-proxy-click");
    let selectors = selector.split(";");
    let parent = false;
    let child = false;
    if (selectors.length == 2) {
      parent = el.closest(selectors[0]);
      child = selectors[1];
    } else if (selectors.length === 1) {
      parent = el;
      child = util.$(selectors, parent);
    }
    let proxy = util.$(child, parent);
    if (proxy) proxy.click();
    else console.warn("proxy-click element not found");
  });
})();
