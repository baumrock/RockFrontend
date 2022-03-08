"use strict";

/**
 * Another Light Frontend Editor - ALFRED
 * Bernhard Baumrock, office@baumrock.com
 */
function Alfred() {
}

Alfred.prototype.addIcons = function(el, icons) {
  let wrapper = document.createElement("div");
  wrapper.classList.add('icons');
  let inner = '';
  icons.forEach(function(icon) {
    inner += "<a href='"
        +(icon.href?icon.href:'#')
        +"' "
        +(icon.tooltip ? " title='" + icon.tooltip + "'" : '')
        +"class='icon' data-barba-prevent>"
      +"<img src='/site/modules/RockFrontend/icons/"+icon.icon+".svg'></span>"
      +"<span class='label uk-text-nowrap'>"+icon.label+"</span>"
      +"</a>";
  });
  wrapper.innerHTML = inner;
  el.append(wrapper);
}

Alfred.prototype.init = function(el) {
  el.classList.add('alfred');
  let config = JSON.parse(el.getAttribute('alfred'));
  this.addIcons(el, config.icons);
}

Alfred.prototype.initAll = function() {
  let items = document.querySelectorAll('[alfred]:not(.alfred)');
  if(!items.length) return;
  items.forEach(function(item) {
    Alfred.init(item);
  });
}

var Alfred = new Alfred();

(function() {
  document.addEventListener('DOMContentLoaded', function() {
    Alfred.initAll();
  });
})()
