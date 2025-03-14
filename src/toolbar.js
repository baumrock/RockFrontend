(() => {
  class Toolbar {
    constructor() {
      this.items = [];
      this.root = document.querySelector("#rockfrontend-toolbar");
      this.callbacks = {};
      this.initItems();
    }

    initItems() {
      this.root.querySelectorAll("#toolbar-tools a").forEach((el) => {
        this.items.push(new Item(el));
      });
    }

    onToggle(toggleName, callback) {
      if (!this.callbacks[toggleName]) this.callbacks[toggleName] = [];
      this.callbacks[toggleName].push(callback);
    }

    triggerCallbacks(toggleName, type) {
      if (!this.callbacks[toggleName]) return;
      const callbacks = this.callbacks[toggleName];
      callbacks.forEach((callback) => callback(type));
    }
  }

  class Item {
    constructor(el) {
      this.el = el;
      this.toolbar = el.closest("#rockfrontend-toolbar");
      this.toggleName = el.getAttribute("data-toggle");
      this.key = this.toggleName
        ? "rf-toolbar-toggle-" + this.toggleName
        : false;
      this.persist = el.hasAttribute("data-persist");
      this.initToggle();
      this.addClickListener();
    }

    addClickListener() {
      this.el.addEventListener("click", (e) => {
        if (this.toggleName) e.preventDefault();
        this.toggle();
      });
    }

    initToggle() {
      if (!this.toggleName) return;
      // get current toggle state
      // if it's on localstorage use this value
      // otherwise check if the class is present on the toolbar
      const storage = localStorage.getItem(this.key);

      if (storage === null) {
        // no localstorage entry found --> toggle depending on class
        this.toolbar.classList.contains(this.toggleName) ||
        this.el.classList.contains("on")
          ? this.on()
          : this.off();
      } else {
        // use value from localstorage
        if (storage === "1") this.on();
        else this.off();
      }
    }

    on() {
      this.el.classList.remove("off");
      this.el.classList.add("on");
      this.toolbar.classList.add(this.toggleName);
      if (this.persist) localStorage.setItem(this.key, "1");
      if (window.RockFrontendToolbar) {
        window.RockFrontendToolbar.triggerCallbacks(this.toggleName, "on");
      }
    }

    off() {
      this.el.classList.remove("on");
      this.el.classList.add("off");
      this.toolbar.classList.remove(this.toggleName);
      if (this.persist) localStorage.setItem(this.key, "0");
      if (window.RockFrontendToolbar) {
        window.RockFrontendToolbar.triggerCallbacks(this.toggleName, "off");
      }
    }

    toggle() {
      if (this.el.classList.contains("on")) this.off();
      else this.on();
    }
  }

  window.RockFrontendToolbar = new Toolbar();
})();
