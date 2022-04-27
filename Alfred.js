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
        +"class='icon "+icon.class+"'"
        +" data-barba-prevent "+icon.suffix+">"
      +"<img src='/site/modules/RockFrontend/icons/"+icon.icon+".svg'></span>"
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
    console.log('Alfred is ready');

    // reload page when an editing modal is closed
    // jquery is loaded, so alfred is ready
    let $ = jQuery;
    let getJ = function() {
      console.log('Loading jQuery...');
      $ = jQuery;
      if(typeof $ == 'function') return ready();
      setTimeout(getJ, 100);
    }

    // attach jquery listeners when it is ready
    let ready = function() {
      $(document).on('pw-modal-closed', 'a[data-reload]', function(e, eventData) {
        if(eventData.abort) return; // modal.js populates 'abort' if "x" button was clicked
        console.log('reloading...');
        location.reload();
      });
      $(document).on("dialogresizestop", ".ui-resizable", function( event, ui ) {
        console.log(event);
      } );
    }

    getJ();
  });
})()
