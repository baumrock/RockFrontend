"use strict";

(function() {
  let $ = null; // jQuery

  /**
   * Another Light Frontend Editor - ALFRED
   * Bernhard Baumrock, office@baumrock.com
   */
  function Alfred() {
  }

  Alfred.prototype.addIcons = function(el, icons) {
    let html = "<div class=icons>";
    icons.forEach(function(icon) {
      html += "<a href='"
          +(icon.href?icon.href:'#')
          +"' "
          +(icon.tooltip ? " title='" + icon.tooltip + "'" : '')
          +"class='icon "+icon.class+"'"
          +" data-barba-prevent "+icon.suffix+">"
        +"<img src='/site/modules/RockFrontend/icons/"+icon.icon+".svg'></span>"
        +"</a>";
    });
    html += "</div>";
    $(el).append(html);
  }

  Alfred.prototype.init = function() {
    let items = document.querySelectorAll('[alfred]:not(.alfred)');
    if(!items.length) return;
    items.forEach(function(item) {
      Alfred.initItem(item);
    });
  }

  Alfred.prototype.initItem = function(el) {
    $(el).addClass('alfred');
    let config = JSON.parse($(el).attr('alfred'));
    this.addIcons(el, config.icons);
    if(config.addTop) $(el).append(this.plus('top', config.addTop));
    if(config.addBottom) $(el).append(this.plus('bottom', config.addBottom));
  }

  Alfred.prototype.plus = function(type, href) {
    return "<div class='add-"+type+"'>"
      +"<a href='"+href+"' title='Add Content' class='icon pw-modal' "
        +" data-barba-prevent='' data-buttons='button.ui-button[type=submit]'"
        +" data-autoclose='' data-reload=''>"
        +"<img src='/site/modules/RockFrontend/icons/plus.svg'>"
      +"</a>"
    +"</div>";
  }

  Alfred.prototype.ready = function(callback) {
    document.addEventListener('DOMContentLoaded', function() {
      let tries = 0;
      let load = function() {
        console.log('Loading jQuery...');
        if(typeof jQuery == 'undefined') {
          if(++tries<=10) setTimeout(load, 100);
          else console.log("jQuery not found");
          return;
        }
        $ = jQuery;
        Alfred.init();
        callback();
      }
      load();
    });
  }

  var Alfred = new Alfred();

  // actions to do when alfred and jquery are ready
  Alfred.ready(function() {
    console.log('Alfred is ready');

    $(document).on('pw-modal-closed', 'a[data-reload]', function(e, eventData) {
      if(eventData.abort) return; // modal.js populates 'abort' if "x" button was clicked
      console.log('reloading...');
      location.reload();
    });
  });

})()
