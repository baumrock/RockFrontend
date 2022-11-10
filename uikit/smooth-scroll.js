// smooth scroll for anchor links
// intercepts all clicks on links that have a # at first position
// and then scroll to that element with an offset of 50px
(function () {
  let util = UIkit.util;
  util.on(document, "click", function (e) {
    let href = util.attr(e.target, "href");
    if (!href) return;
    if (href.indexOf("#") !== 0) return;
    if (util.hasAttr(e.target, "uk-scroll")) return;
    e.preventDefault();
    util.scrollIntoView(util.$(href), { offset: 50 });
  });
})();
