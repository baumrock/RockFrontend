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
          +" uk-tooltip data-barba-prevent "+(icon.suffix||'')+">"
        +"<img src='"+RockFrontend.rootUrl+"site/modules/RockFrontend/icons/"+icon.icon+".svg'></span>"
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
    try {
      let config = JSON.parse($(el).attr('alfred'));
      this.addIcons(el, config.icons);
      if(config.widgetStyle) $(el).addClass('rmx-widget');
      if(config.addTop) $(el).append(this.plus('top', config.addTop));
      if(config.addBottom) $(el).append(this.plus('bottom', config.addBottom));
    } catch (error) {
      alert('invalid json in alfred - dont forget |noescape filter when working with latte files')
    }
  }

  Alfred.prototype.plus = function(type, href) {
    return "<div class='add-"+type+"'>"
      +"<a href='"+href+"' title='Add Content ("+type+")' uk-tooltip class='icon pw-modal' "
        +" data-barba-prevent='' data-buttons='button.ui-button[type=submit]'"
        +" data-autoclose='' data-reload=''>"
        +"<img src='"+RockFrontend.rootUrl+"site/modules/RockFrontend/icons/plus.svg'>"
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
        let url = RockFrontend.rootUrl;

        // load fontawesome
        // this is necessary for the close icon and for the modal loading spinner
        $('head').append('<link rel="stylesheet" href="'+url+'wire/templates-admin/styles/font-awesome/css/font-awesome.min.css" type="text/css">');

        // load vex for delete block confirm dialog
        if(typeof vex == 'undefined') {
          $('head').append('<script src="'+url+'wire/modules/Jquery/JqueryUI/vex/scripts/vex.combined.min.js"></script>');
          $('head').append('<link rel="stylesheet" href="'+url+'wire/modules/Jquery/JqueryUI/vex/css/vex.css">');
          $('head').append('<link rel="stylesheet" href="'+url+'wire/modules/Jquery/JqueryUI/vex/css/vex-theme-default.css">');
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
    console.log('ALFRED is ready :)');

    // reload page when modal is closed
    $(document).on('pw-modal-closed', 'a[data-reload]', function(e, eventData) {
      if(eventData.abort) return; // modal.js populates 'abort' if "x" button was clicked
      console.log('reloading...');
      Alfred.reload();
    });

    // add loaded class to iframe for css transition (fade in)
    $(document).on('pw-modal-opened', function() {
      let $iframe = $("iframe.pw-modal-window");
      $iframe.on('load', function() {
        $iframe.addClass('pw-modal-loaded');
      });
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
          unsafeMessage: confirm + "<div style='margin-top:10px;'><small>Tip: Hold down SHIFT to do this without confirmation.</small></div>",
          callback: function (value) {
            if(value !== true) return;
            sendAjax();
          }
        });
      }
    });

    // edit block on double click
    $(document).on('dblclick', function(e) {
      let $alfred = $(e.target).closest('.alfred');
      // if we are currently inline-editing somthing in this block
      // we do not click the button to open the modal!
      if($alfred.find('*:not(.alfred) .pw-editing').length) return;
      if($alfred.find('*:not(.alfred) .pw-edited').length) return;
      $alfred.find('> .icons > a.alfred-edit').click();
    });
  });

})()
