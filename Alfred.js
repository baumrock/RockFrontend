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
          +(icon.confirm ? " data-confirm='"+icon.confirm+"'" : '')
          +" data-barba-prevent "+(icon.suffix||'')+">"
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
      // load jquery
      let tries = 0;
      let load = function() {
        console.log('Loading jQuery...');
        if(typeof jQuery == 'undefined') {
          if(++tries<=20) setTimeout(load, 500);
          else console.log("jQuery not found");
          return;
        }
        $ = jQuery;
        Alfred.init();

        // load vex
        if(typeof vex == 'undefined') {
          $('head').append('<script src="/wire/modules/Jquery/JqueryUI/vex/scripts/vex.combined.min.js"></script>');
          $('head').append('<link rel="stylesheet" href="/wire/modules/Jquery/JqueryUI/vex/css/vex.css">');
          $('head').append('<link rel="stylesheet" href="/wire/modules/Jquery/JqueryUI/vex/css/vex-theme-default.css">');
          $('head').append('<script>vex.defaultOptions.className="vex-theme-default";');
        }

        callback();
      }
      load();
    });
  }

  /**
   * Reload page (keeps scroll position)
   */
  Alfred.prototype.reload = function() {
    location.reload();
  }

  var Alfred = new Alfred();

  // actions to do when alfred and jquery are ready
  Alfred.ready(function() {
    console.log('Alfred is ready');

    $(document).on('pw-modal-closed', 'a[data-reload]', function(e, eventData) {
      if(eventData.abort) return; // modal.js populates 'abort' if "x" button was clicked
      console.log('reloading...');
      Alfred.reload();
    });

    // clicks on confirm links
    $(document).on('click', '.icons [data-confirm]', function(e) {
      e.preventDefault();
      let $a = $(e.target).closest('a');
      let confirm = $a.data('confirm');
      let href = $a.attr('href');

      // vex dialog in case of error
      let error = function(message) {
        vex.dialog.alert(message || 'Error sending request');
      }

      // ajax request to send
      let sendAjax = function() {
        $.getJSON(href).done(function(data) {
          if(data.error) error("Error: "+data.message);
          else Alfred.reload();
        }).fail(error);
      }

      // on shift click we send the request directly without confirm
      if(e.shiftKey) sendAjax();
      else {
        // show confirm dialog
        vex.dialog.confirm({
          unsafeMessage: confirm + "<div style='margin-top:10px;'><small>Tip: Hold down SHIFT to trash elements without confirmation.</small></div>",
          callback: function (value) {
            if(value !== true) return;
            sendAjax();
          }
        });
      }
    });
  });

})()
