(() => {
  class Toolbar {
    constructor() {
      this.items = [];
      this.root = document.querySelector("#rockfrontend-toolbar");
      this.initItems();
    }

    initItems() {
      this.root.querySelectorAll("#toolbar-tools a").forEach((el) => {
        this.items.push(new Item(el));
      });
    }
  }

  class Item {
    constructor(el) {
      this.el = el;
      this.toolbar = el.closest("#rockfrontend-toolbar");
      this.toggleName = el.getAttribute("data-toggle");
      this.initToggle();
      this.addClickListener();
      console.log(el);
    }

    addClickListener() {
      this.el.addEventListener("click", () => {
        this.toggle();
      });
    }

    initToggle() {
      if (!this.toggleName) return;
      // if toolbar has toggle class then set this item on
      // otherwise set it off
      if (this.toolbar.classList.contains(this.toggleName)) this.on();
      else this.off();
    }

    on() {
      this.el.classList.remove("off");
      this.el.classList.add("on");
      this.toolbar.classList.add(this.toggleName);
    }

    off() {
      this.el.classList.remove("on");
      this.el.classList.add("off");
      this.toolbar.classList.remove(this.toggleName);
    }

    toggle() {
      if (this.el.classList.contains("on")) this.off();
      else this.on();
    }
  }

  window.RockFrontendToolbar = new Toolbar();
})();
