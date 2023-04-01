"use strict";

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
    return this.getStorage()[name];
  };

  // init all containers and checkboxes
  Consent.prototype.init = function () {
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
      let name = el.getAttribute("rfconsent-name");
      let optout = el.getAttribute("rfconsent") == "optout";

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

  RockFrontend.Consent = C;
  C.init();
})();
