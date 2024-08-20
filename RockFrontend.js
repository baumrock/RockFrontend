"use strict";

if (typeof RockFrontend === "undefined") var RockFrontend = {};

// RockFrontend helper functions
(() => {
  /**
   * Debounce
   * Usage:
   * window.addEventListener("mousemove", () => {
   *   RockFrontend.debounce(() => {
   *     console.log("mouse moved");
   *   }, 500);
   * });
   * Note: If you move the mouse continuously the callback will not
   * fire! It will fire 500ms after you stopped moving the mouse, which is
   * what you might want or not. It's good for sending search requests on
   * <input> elements after the user stopped typing, but it might not be what
   * you want if you need to react continuously on some events.
   */
  let timer;
  RockFrontend.debounce = function (func, timeout = 300) {
    clearTimeout(timer);
    timer = setTimeout(() => {
      func();
    }, timeout);
  };
})();

// consent manager
(() => {
  function Consent() {}
  var C = new Consent();

  // array loop helper
  Consent.prototype.each = function (items, callback) {
    for (var i = 0; i < items.length; i++) callback(items[i]);
  };

  // get object from localstorage
  Consent.prototype.getStorage = function () {
    let data = localStorage.getItem("rockfrontend-consent") || "{}";
    return JSON.parse(data);
  };

  // get enabled state for given item
  Consent.prototype.isEnabled = function (name) {
    return !!this.getStorage()[name];
  };

  // get status checked/unchecked
  Consent.prototype.getStatus = function (name) {
    if (this.isEnabled(name)) return "checked";
    return "unchecked";
  };

  // init all containers and checkboxes
  Consent.prototype.init = function () {
    // toggle markup based on consent
    // this will loop through all <template rf-consent="..."> tags
    this.each(document.querySelectorAll("template[rf-consent]"), (el) => {
      let name = el.getAttribute("rf-consent");
      let status = this.getStatus(name);

      // the "loadif" attribute defines whether to load this markup or not
      // based on the value of the consent checkbox
      let loadif = el.getAttribute("loadif");

      // the target for the template content is <div rf-consent=name>
      // that div can be placed by the user wherever he/she needs
      // if that div does not exist it will be added to the dom automatically
      let target = document.querySelector("div[rf-consent=" + name + "]");
      if (!target) {
        // div does not exist -> insert it before the <template> tag
        target = document.createElement("div");
        target.setAttribute("rf-consent", name);
        el.parentNode.insertBefore(target, el);
      }
      if (loadif === status) {
        target.innerHTML = "";
        target.append(el.content.cloneNode(true));
      }
    });

    // show containers for enabled services
    let show = document.querySelectorAll("[data-rfc-show]");
    this.each(show, function (container) {
      let name = container.getAttribute("data-rfc-show");
      if (!C.isEnabled(name)) return;
      container.removeAttribute("hidden");
      let el = container.querySelector("[rfconsent-src]");
      let src = el.getAttribute("rfconsent-src");
      el.setAttribute("src", src);
    });

    // enable scripts that don't have an alternate markup
    let enable = document.querySelectorAll("[rfconsent-src]");
    this.each(enable, function (el) {
      let name = el.getAttribute("rfconsent");
      let optout = el.getAttribute("rfconsent-type") == "optout";

      // on optout scripts we set the consent automatically if no entry exists
      if (optout && typeof C.getStorage()[name] == "undefined") {
        C.save(name, true);
      }

      if (!C.isEnabled(name)) return;
      let src = el.getAttribute("rfconsent-src");
      el.setAttribute("src", src);
    });

    // hide alternate containers for enabled services
    let hide = document.querySelectorAll("[data-rfc-hide]");
    this.each(hide, function (container) {
      let name = container.getAttribute("data-rfc-hide");
      if (C.isEnabled(name)) {
        container.setAttribute("hidden", true);
      } else {
        container.removeAttribute("hidden");
      }
    });

    // toggle checkboxes based on enabled state
    let checkboxes = document.querySelectorAll(".rf-consent-checkbox");
    this.each(checkboxes, (cb) => {
      let name = cb.getAttribute("data-name");
      if (this.isEnabled(name)) {
        cb.setAttribute("checked", "checked");
      } else {
        cb.removeAttribute("checked");
      }
    });

    // debugging
    // console.log(C.getStorage());
  };

  // save consent state to localstorage
  Consent.prototype.save = function (name, value) {
    let storage = this.getStorage();
    storage[name] = value;
    localStorage.setItem("rockfrontend-consent", JSON.stringify(storage));
  };

  // monitor checkbox changes
  document.addEventListener("change", function (e) {
    let cb = e.target;
    if (!cb.classList.contains("rf-consent-checkbox")) return;
    let name = cb.getAttribute("data-name");
    C.save(name, cb.checked);
    C.init();
  });

  // monitor clicks on consent buttons
  document.addEventListener("click", (e) => {
    let el = e.target.closest("[rfc-allow]");
    if (!el) return;
    e.preventDefault();
    let name = el.getAttribute("rfc-allow");
    C.save(name, true);
    C.init();
  });

  // monitor clicks on elements having "has-consent-click"
  // see boukal for an example
  document.addEventListener("click", function (e) {
    let click = e.target.closest("[has-consent-click]");
    if (!click) return;

    let selector = click.getAttribute("has-consent-click");
    let target = document.querySelector(selector);
    // if no target was found we let the regular click through
    // this should be an anchor with target=_blank
    if (!target) return;

    e.preventDefault();
    let name = click.getAttribute("rf-consent");
    if (C.isEnabled(name)) {
      target.click();
    } else {
      selector = click.getAttribute("needs-consent-click");
      target = document.querySelector(selector);
      if (target) target.click();
    }
  });

  // populate src from rfc-src whenever a uikit modal is opened
  // see boukal for an example
  document.addEventListener("beforeshow", function (e) {
    let modal = e.target;
    let iframes = modal.querySelectorAll("iframe[rfc-src]");
    iframes.forEach(function (iframe) {
      iframe.setAttribute("src", iframe.getAttribute("rfc-src"));
    });
  });

  // remove src attribute whenever a uikit modal is closed
  // see boukal for an example
  document.addEventListener("beforehide", function (e) {
    let modal = e.target;
    let iframes = modal.querySelectorAll("iframe[rfc-src]");
    iframes.forEach(function (iframe) {
      iframe.removeAttribute("src");
    });
  });

  // intercept links with "rfconsent-click" attributes
  let allowAttached = false;
  document.addEventListener("click", (e) => {
    let el = e.target.closest("[rfconsent-click]");
    if (!el) return;
    e.preventDefault();
    let name = el.getAttribute("rfconsent");
    let click = el.getAttribute("rfconsent-click");
    let allow = el.getAttribute("rfconsent-allow");
    let ask = el.getAttribute("rfconsent-ask");
    if (C.isEnabled(name)) document.querySelector(click).click();
    else {
      // attach listener to element that confirms the dialog
      if (!allowAttached) {
        document.addEventListener("click", (e) => {
          // we only grant consent if the allow selector matches
          // otherwise the cancel button was clicked
          if (!e.target.matches(allow)) return;

          // save consent
          C.save(name, true);

          // now click the element defined in the "click" property
          document.querySelector(click).click();
        });
        allowAttached = true;
      }

      // trigger confirmation click
      document.querySelector(ask).click();
    }
  });

  RockFrontend.Consent = C;
  C.init();
})();

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
  for (let i = 0; i < scrollElements.length; i++) {
    let el = scrollElements[i];
    let attrs = el.getAttribute("rf-scrollclass").split(" ");
    for (let j = 0; j < attrs.length; j++) {
      let parts = attrs[j].split("@");
      if (parts.length != 2) return;
      let cls = parts[0];
      let y = parts[1] * 1;
      window.addEventListener("scroll", function () {
        let scrollpos = window.scrollY;
        if (scrollpos >= y) el.classList.add(cls);
        else el.classList.remove(cls);
      });
    }
  }
})();

/**
 * Trigger a click on another element
 * Usage:
 * <a rf-click="#foo">click the #foo element</a>
 * <div uk-lightbox>
 *   <a href="/foo/bar.jpg" id="foo">Show Image</a>
 * </div>
 */
document.addEventListener("click", function (e) {
  let click = e.target.closest("[rf-click]");
  if (!click) return;
  e.preventDefault();
  let selector = click.getAttribute("rf-click");
  let target = document.querySelector(selector);
  if (target) target.click();
});

/**
 * Toggle the "hidden" attribute of another element
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
