"use strict";

(function(){
  let util = UIkit.util;
  let id = 0;

  // ########## GridDropdowns ##########

  // class to hold all instances of dropdowns
  function GridDropdowns() {
    this.dropdowns = {};
  }

  /**
   * Get dropdown by id
   */
  GridDropdowns.prototype.get = function(id) {
    return this.dropdowns[id];
  }

  /**
   * Get dropdown are init a new one
   */
  GridDropdowns.prototype.getDropdown = function(el) {
    let dropdown = false;

    // option 1: a toggle button was clicked
    let toggle = el.closest('.rf-griddropdown-toggle');
    if(toggle) {
      let id = util.data(toggle, 'griddropdown-id');
      if(!id) {
        // create a new dropdown
        dropdown = new GridDropdown(toggle);
        this.dropdowns[dropdown.id] = dropdown;
      }
      else dropdown = this.dropdowns[id];
      return dropdown;
    }

    // option 2: an element was provided to return the dropdown
    // in this case we dont create a new dropdown if we dont find one
    el = el.closest('[data-griddropdown-id]');
    let id = util.data(el, 'griddropdown-id');
    return dropdowns.get(id);
  }

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
    util.attr(this.gridItem, 'data-griddropdown-id', this.id);
    util.attr(toggle, 'data-griddropdown-id', this.id);

    // debug: log all dropdowns
    console.log(dropdowns.dropdowns);
  }

  /**
   * Close the info popup
   */
  GridDropdown.prototype.close = function() {
    if(!this.isOpen) return;

    // hide close icon (fix layout shift)
    let close = util.$$('[uk-close]', this.getAcc());
    util.addClass(close, 'uk-hidden');

    // toggle accordion
    this.getAcc(true).toggle();
  }

  /**
   * Close all dropdowns of current row
   */
  GridDropdown.prototype.closeAllInRow = function(doneCallback) {
    let items = this.rowItems();
    util.each(items, function(item) {
      let dropdown = dropdowns.getDropdown(item);
      if(typeof dropdown == 'undefined') return;
      if(!dropdown.isOpen) return;
      dropdown.close();
    });

    let i = 0;
    items = this.rowItems(true);
    console.log('check all closed?', items);
    let loop = setInterval(() => {
      console.log('checking');
      let open = false;
      util.each(items, (item) => {
        if(item.isOpen) open = true;
      });
      if(!open) {
        clearInterval(loop);
        doneCallback();
      }
    }, 50);
  }

  /**
   * Create the div to place the popup
   */
  GridDropdown.prototype.createAccordion = function() {
    let items = this.rowItems();
    let last = items.slice(-1)[0];
    util.after(last,
      "<div class='rf-griddropdown-container uk-width-1-1 uk-margin-remove' data-griddropdown-id='"+this.id+"'>"
        +'<ul class="uk-margin-remove" uk-accordion>'
          +'<li>'
            +'<div class="uk-accordion-content uk-position-relative rf-griddropdown-inner"></div>'
          +'</li>'
        +'</ul>'
      +"</div>"
    );

    let info = this.getInfo(true);
    util.html(info, this.getMarkup());

    // fix close button layout shift issue
    util.addClass(util.$$('[uk-close]', info), 'uk-hidden');

    return this.getAcc(true);
  }

  /**
   * Populate the gridItem property of this object
   */
  GridDropdown.prototype.findGridItem = function(el) {
    while(el.parentNode) {
      this.gridItem = el;
      el = el.parentNode;
      if(util.hasClass(el, 'rf-griddropdown')) return;
    }
  }

  /**
   * Get accordion element
   */
  GridDropdown.prototype.getAcc = function(getComponent) {
    let acc = util.$('[uk-accordion]', this.getInfo());
    if(getComponent) return UIkit.accordion(acc);
    return acc;
  }

  /**
   * Get info dropdown element (or inner element)
   */
  GridDropdown.prototype.getInfo = function(inner) {
    inner = inner ? ' .rf-griddropdown-inner' : '';
    return util.$('.rf-griddropdown-container[data-griddropdown-id="'+this.id+'"] '+inner);
  }

  /**
   * Get the markup of the info dropdown
   */
  GridDropdown.prototype.getMarkup = function() {
    return this.markup.innerHTML;
  }

  /**
   * Return the grid element of the current popup
   */
  GridDropdown.prototype.grid = function() {
    return this.toggle.closest('.rf-griddropdown');
  }

  /**
   * Get all griditems
   */
  GridDropdown.prototype.getItems = function() {
    if(this.gridItems) return this.gridItems;
    let items = [];
    util.each(util.$$('>*', this.grid()), function(griditem) {
      if(util.hasClass(griditem, 'rf-griddropdown-container')) return;
      items.push(griditem);
    });
    return this.gridItems = items;
  }

  /**
   * Return all items of the row the item lives in (currently)
   * Note that this can change on every click!
   */
  GridDropdown.prototype.rowItems = function(returnObjects) {
    let rowitems = [];
    let dropdown = this;
    let top = this.top();
    // console.log(top, 'top');
    util.each(this.getItems(), function(item) {
      let ctop = dropdown.top(item);
      if(ctop != top) return;
      if(returnObjects) item = dropdowns.getDropdown(item);
      if(item) rowitems.push(item);
    });
    return rowitems;
  }

  /**
   * Show the popup
   */
  GridDropdown.prototype.show = function() {
    if(this.isOpen) return;
    if(this.showing) return; // prevents doubleclick issues
    let dropdown = this;
    this.showing = true;
    // we fetch acc outside of the callback to make sure
    // it is ready when we need it and we dont need another settimeout
    let acc = dropdown.createAccordion();
    this.closeAllInRow(() => {
      // isOpen is set when the opening starts
      // it is removed when the closing has finished
      dropdown.isOpen = true;
      dropdown.showing = false;
      acc.toggle();
    });
  }

  /**
   * Get top offset of element
   */
  GridDropdown.prototype.top = function(el) {
    el = el || this.gridItem;
    return el.getBoundingClientRect().top
  }

  // ########## init ##########

  // init dropdowns class
  let dropdowns = new GridDropdowns();

  // handle clicks on toggle
  util.on('.rf-griddropdown-toggle', 'click', function(e) {
    e.preventDefault();
    let dropdown = dropdowns.getDropdown(e.target);
    dropdown.show();
  });

  // listen to toggle closed events
  util.on(document, 'hidden', function(e) {
    let el = e.target;
    if(!util.$('>div.rf-griddropdown-inner', el)) return;
    let dropdown = dropdowns.getDropdown(el);
    // console.log(dropdown);

    // isOpen is set when the opening starts
    // it is removed when the closing has finished
    dropdown.isOpen = false;
  });

  // listen to toggle opened events
  util.on(document, 'shown', function(e) {
    let el = e.target;
    if(!util.$('>div.rf-griddropdown-inner', el)) return;
    let dropdown = dropdowns.getDropdown(el);

    // show close button
    let close = util.$$('[uk-close]', dropdown.getAcc());
    util.removeClass(close, 'uk-hidden');
  });

  // auto-close open dropdowns
  util.on(document, 'click', function(e) {
    let el = e.target.closest('[uk-close]');
    if(!el) return;
    let dropdown = dropdowns.getDropdown(el);
    dropdown.close();
  });

})();
