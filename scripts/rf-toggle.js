/**
 * Toggle the "hidden" attribute of another element
 * Usage:
 * <a href=# rf-toggle='#foo'>toggle foo</a>
 * <div id='foo'>hello world</div>
 */
(() => {
  document.addEventListener("click", function (e) {
    let toggle = e.target.closest("[rf-toggle]");
    if (!toggle) return;
    e.preventDefault();
    let selector = toggle.getAttribute("rf-toggle");
    let target = document.querySelector(selector);
    if (target.hasAttribute("hidden")) target.removeAttribute("hidden");
    else target.setAttribute("hidden", "");
  });
})();
