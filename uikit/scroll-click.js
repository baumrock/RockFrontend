/**
 * Intercepts clicks on links
 * Add class "uk-animation-fade" to body element
 */
(() => {
  let util = UIkit.util;

  util.on(document, "click", function (e) {
    let el = e.target.closest("a");
    if (!el) return;

    // early exits
    if (el.closest("[rf-toggle]")) return;
    if (el.closest("[uk-toggle]")) return;
    if (el.closest("[uk-lightbox]")) return;
    if (el.closest(".uk-lightbox")) return;
    if (el.closest(".alfredelements")) return;
    if (el.closest("a.pw-modal")) return;
    if (el.closest("a.pw-panel")) return;
    if (el.closest(".tracy-panel")) return;
    if (el.closest(".cke")) return; // ckeditor
    if (el.closest(".uk-slider-nav")) return;
    if (el.closest(".pw-edit")) return; // frontend-edit

    // no fade link?
    if (util.hasClass(el, "rf-no-fade")) return;
    if (util.hasAttr(el, "target")) return;

    // check href
    let href = util.attr(el, "href");
    if (href.indexOf("#") === 0) return;
    if (href.indexOf("tel:") === 0) return;
    if (href.indexOf("mailto:") === 0) return;

    // scroll + fade
    e.preventDefault();
    let body = util.$("body");
    util.scrollIntoView(body).then(function () {
      window.location.href = href;
    });
  });
})();
