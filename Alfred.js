"use strict";

(function () {
  let $ = null; // jQuery

  /**
   * Another Light Frontend Editor - ALFRED
   * Bernhard Baumrock, office@baumrock.com
   */
  function Alfred() {}

  Alfred.prototype.addIcons = function (el, icons, type) {
    let html = "<div class=icons>";
    let id = $(el).parent().attr("id") || "";
    if (id) {
      id = "<span style='margin-right: 7px;'>#" + id + "</span>";
    }
    if (type || id) {
      type = type || "";
      id = id || "";
      html += "<span class='rpb-type'><span>" + id + type + "</span></span>";
    }
    icons.forEach(function (icon) {
      // console.log(icon);
      html +=
        "<a " +
        Alfred.href(icon) +
        " class='icon " +
        icon.class +
        "'" +
        (icon.confirm ? " data-confirm='" + icon.confirm + "'" : "") +
        " data-barba-prevent " +
        (icon.suffix || "") +
        ">" +
        "<img src='" +
        RockFrontend.rootUrl +
        "site/modules/RockFrontend/icons/" +
        icon.icon +
        ".svg' " +
        ">" +
        Alfred.vspace(icon) +
        Alfred.tooltip(icon) +
        "</a>";
    });
    html += "</div>";
    $(el).append(html);
  };

  Alfred.prototype.tooltip = function (icon) {
    if (!icon.tooltip) return "";
    return (
      "<span class='alfred-cover' title='" +
      icon.tooltip +
      "' uk-tooltip></span>"
    );
  };

  Alfred.prototype.icon = function (name) {
    return (
      "<img src='" +
      RockFrontend.rootUrl +
      "site/modules/RockFrontend/icons/" +
      name +
      ".svg'>"
    );
  };

  Alfred.prototype.href = function (icon) {
    if (icon.type == "vspacetop") return;
    if (icon.type == "vspacebottom") return;
    return "href='" + (icon.href ? icon.href : "#") + "'";
  };

  Alfred.prototype.vspace = function (icon) {
    if (!RockFrontend.vspaceGUI) return "";
    return RockFrontend.vspaceGUI(icon);
  };

  Alfred.prototype.init = function () {
    // early exit when in mobile preview iframe
    if (document.querySelector("body").classList.contains("rpb-preview")) {
      return;
    }
    let items = document.querySelectorAll("[alfred]:not(.alfred)");
    if (!items.length) return;
    items.forEach(function (item) {
      Alfred.initItem(item);
    });
  };

  Alfred.prototype.initItem = function (el) {
    $(el).addClass("alfred");
    $(el).append("<div class='alfredelements'>");
    let $elements = $(el).find(".alfredelements");
    try {
      let config = JSON.parse($(el).attr("alfred"));
      this.addIcons($elements, config.icons, config.type);
      if (config.widgetStyle) $(el).addClass("rpb-widget");
      if (config.addTop) $elements.append(this.plus("top", config.addTop));
      if (config.addBottom)
        $elements.append(this.plus("bottom", config.addBottom));
      if (config.addLeft) $elements.append(this.plus("left", config.addLeft));
      if (config.addRight)
        $elements.append(this.plus("right", config.addRight));

      if ($elements.closest("[sortable]").length) {
        $elements.append(
          '<div class="sortable-handle"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m18 9l3 3l-3 3m-3-3h6M6 9l-3 3l3 3m-3-3h6m0 6l3 3l3-3m-3-3v6m3-15l-3-3l-3 3m3-3v6"/></svg></div>'
        );
      }
      $(el).removeAttr("alfred");
    } catch (error) {
      console.log($elements);
      alert(
        "invalid json in alfred - dont forget |noescape filter when working with latte files. The element that threw this error was logged to the console."
      );
    }
  };

  Alfred.prototype.plus = function (type, href) {
    return (
      "<div class='add-" +
      type +
      "'>" +
      "<a href='" +
      href +
      "' title='Add Content (" +
      type +
      ")' uk-tooltip class='icon pw-modal' " +
      " data-barba-prevent='' data-buttons='button.ui-button[type=submit]'" +
      " data-autoclose='' data-reload=''>" +
      "<img src='" +
      RockFrontend.rootUrl +
      "site/modules/RockFrontend/icons/plus.svg'>" +
      "</a>" +
      "</div>"
    );
  };

  Alfred.prototype.ready = function (callback) {
    document.addEventListener("DOMContentLoaded", function () {
      // load jquery
      let tries = 0;
      let load = function () {
        console.log("Loading jQuery...");
        if (typeof jQuery == "undefined") {
          if (++tries <= 20) setTimeout(load, 500);
          else console.log("jQuery not found");
          return;
        }
        $ = jQuery;
        Alfred.init();
        let url = RockFrontend.rootUrl;

        // load fontawesome
        // this is necessary for the close icon and for the modal loading spinner
        $("head").append(
          '<link rel="stylesheet" href="' +
            url +
            'wire/templates-admin/styles/font-awesome/css/font-awesome.min.css" type="text/css">'
        );

        // load vex for delete block confirm dialog
        if (typeof vex == "undefined") {
          $("head").append(
            '<script src="' +
              url +
              'wire/modules/Jquery/JqueryUI/vex/scripts/vex.combined.min.js"></script>'
          );
          $("head").append(
            '<link rel="stylesheet" href="' +
              url +
              'wire/modules/Jquery/JqueryUI/vex/css/vex.css">'
          );
          $("head").append(
            '<link rel="stylesheet" href="' +
              url +
              'wire/modules/Jquery/JqueryUI/vex/css/vex-theme-default.css">'
          );
          $("head").append(
            '<script>vex.defaultOptions.className="vex-theme-default";'
          );
        }

        callback();
      };
      load();
    });
  };

  /**
   * Reload page (keeps scroll position)
   */
  Alfred.prototype.reload = function () {
    location.reload();
  };

  var Alfred = new Alfred();
  RockFrontend.Alfred = Alfred;

  // actions to do when alfred and jquery are ready
  Alfred.ready(function () {
    console.log("ALFRED is ready :)");

    // trigger event
    var event = new CustomEvent("AlfredReady", Alfred);
    document.dispatchEvent(event);

    // reload page when modal is closed
    $(document).on(
      "pw-modal-closed",
      "a[data-reload]",
      function (e, eventData) {
        // we populate ui.abort when RockMatrix blocks are added
        // modal.js populates 'abort' if "x" button was clicked
        if (eventData.ui.abort !== false && eventData.abort) return;
        console.log("reloading...");
        Alfred.reload();
      }
    );

    // attach listener to iframe when modal is opened
    // This is to reload the page if a rockmatrix item was created
    // and the modal is not saved but closed via the close icon.
    // To make it obvious for the user that the new block was actually
    // added to the page even without clicking "save" we reload the page
    // whick should make the block appear on the screen.
    $(document).on("pw-modal-opened", function (e, eventData) {
      let iFrame = eventData.event.target;
      let modal = iFrame.closest(".ui-dialog");
      var abort = true;
      iFrame.onload = function (e) {
        let iDoc = iFrame.contentWindow.document;
        $(iDoc).on("click", ".rmx-button", function (e) {
          abort = false;
        });
      };
      $(modal).on("dialogclose", function (event, ui) {
        ui.abort = abort;
      });
    });

    // add loaded class to iframe for css transition (fade in)
    $(document).on("pw-modal-opened", function () {
      let $iframe = $("iframe.pw-modal-window");
      $iframe.on("load", function () {
        $iframe.addClass("pw-modal-loaded");
      });
    });

    // clicks on confirm links
    $(document).on("click", ".icons [data-confirm]", function (e) {
      e.preventDefault();
      let $a = $(e.target).closest("a");
      let confirm = $a.data("confirm");
      let href = $a.attr("href");

      // vex dialog in case of error
      let error = function (message) {
        vex.dialog.alert(message || "Error sending request");
      };

      // ajax request to send
      let sendAjax = function () {
        $.getJSON(href)
          .done(function (data) {
            if (data.error) error("Error: " + data.message);
            else Alfred.reload();
          })
          .fail(error);
      };

      // on shift click we send the request directly without confirm
      if (e.shiftKey) sendAjax();
      else {
        // show confirm dialog
        vex.dialog.confirm({
          unsafeMessage:
            confirm +
            "<div style='margin-top:10px;'><small>Tip: Hold down SHIFT to do this without confirmation.</small></div>",
          callback: function (value) {
            if (value !== true) return;
            sendAjax();
          },
        });
      }
    });

    // edit block on double click
    $(document).on("dblclick", function (e) {
      // add the class .no-alfred to prevent double click alfred, eg in forms
      if (e.target.closest(".no-alfred")) return;

      // dont trigger modal on links and buttons
      if (e.target.closest("button")) return;
      if (e.target.closest("a")) return;

      let $alfred = $(e.target).closest(".alfred");
      // console.log($alfred);
      // if we are currently inline-editing somthing in this block
      // we do not click the button to open the modal!
      if ($alfred.find("> .pw-editing").length) return;
      if ($alfred.find("> .pw-edited").length) return;
      if ($alfred.find("*:not(.alfred) .pw-editing").length) return;
      if ($alfred.find("*:not(.alfred) .pw-edited").length) return;
      $alfred.find("> .alfredelements > .icons > a.alfred-edit").click();
    });

    // toggle topbar if CMD key or CTRL key is pressed
    // do not toggle if multiple keys are pressed at once (cmd+enter)
    let toggleBar = false;
    let numKeys = 0;
    document.addEventListener("keyup", function (event) {
      numKeys = 0;
      if (!toggleBar) return;
      toggleBar = false; // reset
      let topbar = document.querySelector("#rf-topbar");
      if (topbar) {
        if (topbar.classList.contains("hide")) {
          topbar.click();
        } else {
          topbar.querySelector(".rf-topbar-hide").click();
        }
      } else {
        let body = document.querySelector("body");
        if (localStorage.getItem("rf-topbar-hide") == "0") {
          body.classList.add("no-alfred");
          localStorage.setItem("rf-topbar-hide", 1);
        } else {
          body.classList.remove("no-alfred");
          localStorage.setItem("rf-topbar-hide", 0);
        }
      }
    });
    document.addEventListener("keydown", function (event) {
      numKeys++;
      if (numKeys > 1) {
        toggleBar = false;
        return;
      }
      if (!(event.metaKey || event.ctrlKey)) return;
      toggleBar = true;
    });
  });
})();
