"use strict";

/**
 * Grid Dropdown UIkit Component
 * v1.3
 */

(function () {
  let util = UIkit.util;
  let id = 0;

  // ########## GridDropdowns ##########

  // class to hold all instances of dropdowns
  function GridDropdowns() {
    this.dropdowns = {};
  }

  /**
   * Close all given items
   */
  GridDropdowns.prototype.closeAll = function (items, callback) {
    util.each(items, function (item) {
      if (typeof item == "undefined") return;
      if (!item.isOpen) return;
      item.close();
    });
    this.whenClosed(items, callback);
  };

  /**
   * Reload all open dropdowns (when window is resized)
   */
  GridDropdowns.prototype.reloadAll = function () {
    let openItems = this.getOpen(this.dropdowns);
    let afterClose = () => {
      // let last;
      util.each(openItems, (item) => {
        item.show();
        // last = item;
      });
      // util.scrollIntoView(last);
    };
    this.closeAll(this.dropdowns, afterClose);
  };

  /**
   * Get dropdown by id
   */
  GridDropdowns.prototype.get = function (id) {
    return this.dropdowns[id];
  };

  /**
   * Get dropdown are init a new one
   */
  GridDropdowns.prototype.getDropdown = function (el) {
    let dropdown = false;

    // option 1: a toggle button was clicked
    let toggle = el.closest(".rf-griddropdown-toggle");
    if (toggle) {
      let id = util.data(toggle, "griddropdown-id");
      if (!id) {
        // create a new dropdown
        dropdown = new GridDropdown(toggle);
        this.dropdowns[dropdown.id] = dropdown;
      } else dropdown = this.dropdowns[id];
      return dropdown;
    }

    // option 2: an element was provided to return the dropdown
    // in this case we dont create a new dropdown if we dont find one
    el = el.closest("[data-griddropdown-id]");
    let id = util.data(el, "griddropdown-id");
    return dropdowns.get(id);
  };

  /**
   * Get all open items
   */
  GridDropdowns.prototype.getOpen = function (items) {
    let open = [];
    util.each(items, (item) => {
      if (item.isOpen) open.push(item);
    });
    return open;
  };

  /**
   * Helper that executes a callback when all items have been closed
   */
  GridDropdowns.prototype.whenClosed = function (items, callback) {
    // console.log('check all closed?', items);
    let loop = setInterval(() => {
      // console.log('checking');
      let open = false;
      util.each(items, (item) => {
        if (item.isOpen) open = true;
      });
      if (!open) {
        clearInterval(loop);
        callback();
      }
    }, 50);
  };

  // ########## GridDropdown ##########

  // init class
  function GridDropdown(toggle) {
    this.id = ++id;
    this.toggle = toggle;
    this.markup = toggle.nextElementSibling;
    this.gridItems = false;
    this.gridItem = false;

    // isOpen is set when the opening starts
    // it is removed when the closing has finished
    this.isOpen = false;

    this.findGridItem(toggle);
    util.attr(this.gridItem, "data-griddropdown-id", this.id);
    util.attr(toggle, "data-griddropdown-id", this.id);
  }

  /**
   * Close the info popup
   */
  GridDropdown.prototype.close = function () {
    if (!this.isOpen) return;

    // hide close icon (fix layout shift)
    let close = util.$$("[uk-close]", this.getAcc());
    util.addClass(close, "uk-hidden");

    // toggle accordion
    this.getAcc(true).toggle();
  };

  /**
   * Create the div to place the popup
   */
  GridDropdown.prototype.createAccordion = function () {
    let items = this.rowItems();
    let last = items.slice(-1)[0];
    util.after(
      last,
      "<div class='rf-griddropdown-container uk-width-1-1 uk-margin-remove' data-griddropdown-id='" +
        this.id +
        "'>" +
        '<ul class="uk-margin-remove" uk-accordion>' +
        "<li>" +
        '<div class="uk-accordion-content uk-position-relative rf-griddropdown-inner"></div>' +
        "</li>" +
        "</ul>" +
        "</div>"
    );

    let info = this.getInfo(true);
    util.html(info, this.getMarkup());

    // fix close button layout shift issue
    util.addClass(util.$$("[uk-close]", info), "uk-hidden");

    return this.getAcc(true);
  };

  /**
   * Populate the gridItem property of this object
   */
  GridDropdown.prototype.findGridItem = function (el) {
    while (el.parentNode) {
      this.gridItem = el;
      el = el.parentNode;
      if (util.hasClass(el, "rf-griddropdown")) return;
    }
  };

  /**
   * Get accordion element
   */
  GridDropdown.prototype.getAcc = function (getComponent) {
    let acc = util.$("[uk-accordion]", this.getInfo());
    if (getComponent) return UIkit.accordion(acc);
    return acc;
  };

  /**
   * Get accordion elements container
   */
  GridDropdown.prototype.getAccContainer = function () {
    return this.getAcc().closest(".rf-griddropdown-container");
  };

  /**
   * Get info dropdown element (or inner element)
   */
  GridDropdown.prototype.getInfo = function (inner) {
    inner = inner ? " .rf-griddropdown-inner" : "";
    return util.$(
      '.rf-griddropdown-container[data-griddropdown-id="' +
        this.id +
        '"] ' +
        inner
    );
  };

  /**
   * Get the markup of the info dropdown
   */
  GridDropdown.prototype.getMarkup = function () {
    return this.markup.innerHTML;
  };

  /**
   * Return the grid element of the current popup
   */
  GridDropdown.prototype.grid = function () {
    return this.toggle.closest(".rf-griddropdown");
  };

  /**
   * Get all griditems
   */
  GridDropdown.prototype.getItems = function () {
    if (this.gridItems) return this.gridItems;
    let items = [];
    util.each(util.$$(">*", this.grid()), function (griditem) {
      if (util.hasClass(griditem, "rf-griddropdown-container")) return;
      items.push(griditem);
    });
    return (this.gridItems = items);
  };

  /**
   * Return all items of the row the item lives in (currently)
   * Note that this can change on every click!
   */
  GridDropdown.prototype.rowItems = function (returnObjects) {
    let rowitems = [];
    let dropdown = this;
    let top = this.top();
    // console.log(top, 'top');
    util.each(this.getItems(), function (item) {
      let ctop = dropdown.top(item);
      if (ctop != top) return;
      if (returnObjects) item = dropdowns.getDropdown(item);
      if (item) rowitems.push(item);
    });
    return rowitems;
  };

  /**
   * Set width of arrow container to match the grid item
   */
  GridDropdown.prototype.setArrowWidth = function () {
    let item = this.gridItem;
    let acc = this.getAcc();
    let width = util.width(item);
    let itemleft = item.getBoundingClientRect().left;

    // get reference to compare the left offset with
    let ref = acc.closest(".rf-griddropdown-container");
    let refleft = ref.getBoundingClientRect().left;
    // console.log(itemleft, item);
    // console.log(refleft, ref);

    // find arrow
    let arrow = util.$(".rf-griddropdown-arrow", acc);
    util.addClass(arrow, "uk-position-absolute uk-flex uk-flex-center");
    util.width(arrow, width);
    arrow.style.left = Math.floor(itemleft - refleft) + "px";
  };

  /**
   * Show the popup
   */
  GridDropdown.prototype.show = function () {
    if (this.isOpen) return;
    if (this.showing) return; // prevents doubleclick issues
    let dropdown = this;
    this.showing = true;
    // we fetch acc outside of the callback to make sure
    // it is ready when we need it and we dont need another settimeout
    let acc = dropdown.createAccordion();
    dropdowns.closeAll(this.rowItems(true), () => {
      // isOpen is set when the opening starts
      // it is removed when the closing has finished
      dropdown.isOpen = true;
      dropdown.showing = false;
      dropdown.setArrowWidth();
      acc.toggle();
    });
  };

  /**
   * Get top offset of element
   */
  GridDropdown.prototype.top = function (el) {
    el = el || this.gridItem;
    return el.getBoundingClientRect().top;
  };

  // ########## init ##########

  // init dropdowns class
  let dropdowns = new GridDropdowns();

  // handle clicks on toggle
  util.on(".rf-griddropdown-toggle", "click", function (e) {
    e.preventDefault();
    let dropdown = dropdowns.getDropdown(e.target);
    if (dropdown.isOpen) dropdown.close();
    else {
      dropdown.show();
      util.scrollIntoView(dropdown.gridItem);
    }
  });

  // listen to toggle closed events
  util.on(document, "hidden", function (e) {
    let el = e.target;
    let dropdown = dropdowns.getDropdown(el);
    if (!dropdown) return;
    util.removeClass(dropdown.gridItem, "rf-griddropdown-open");
    dropdown.isOpen = false;
    util.remove(dropdown.getAccContainer());
  });

  // listen to toggle opened events
  util.on(document, "shown", function (e) {
    let el = e.target;
    if (!util.$(">div.rf-griddropdown-inner", el)) return;
    let dropdown = dropdowns.getDropdown(el);
    dropdown.isOpen = true;
    util.addClass(dropdown.gridItem, "rf-griddropdown-open");

    // show close button
    let close = util.$$("[uk-close]", dropdown.getAcc());
    util.removeClass(close, "uk-hidden");
  });

  // auto-close open dropdowns
  util.on(document, "click", function (e) {
    let el = e.target.closest("[uk-close]");
    if (!el) return;
    let dropdown = dropdowns.getDropdown(el);
    if (!dropdown) return;
    dropdown.close();
  });

  // reload all dropdowns on resize
  let timer;
  let screenWidth = util.width(document);
  util.on(window, "resize", function () {
    clearTimeout(timer);
    timer = setTimeout(() => {
      let _screenWidth = util.width(document);
      // only reload windows if the screen width changed
      if (_screenWidth != screenWidth) {
        dropdowns.reloadAll();
        screenWidth = _screenWidth;
      }
    }, 250);
  });
})();
