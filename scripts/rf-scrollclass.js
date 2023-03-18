/**
 * Add/remove class on scroll position
 *
 * Usage:
 * <a href='#' rf-scrollclass='show@300'>Add class "show" at 300px scrollposition</a>
 *
 * Add multiple classes (thx @StefanThumann)
 * <a href='#' rf-scrollclass='show@300 show2@600'>Add class "show" at 300px scrollposition, "show2" at 600px</a>
 */
(function () {
  let scrollElements = document.querySelectorAll("[rf-scrollclass]");
  for (i = 0; i < scrollElements.length; i++) {
    let el = scrollElements[i];
    let attrs = el.getAttribute("rf-scrollclass").split(" ");
    for (j = 0; j < attrs.length; j++) {
      let parts = attrs[j].split("@");
      if (parts.length != 2) return;
      let cls = parts[0];
      let y = parts[1] * 1;
      window.addEventListener("scroll", function () {
        scrollpos = window.scrollY;
        if (scrollpos >= y) el.classList.add(cls);
        else el.classList.remove(cls);
      });
    }
  }
})();
