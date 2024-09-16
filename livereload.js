// live reloading via SSE
// we start listening for changes one second after page load
// this makes sure that the page does not reload immediately after creating a new page
setTimeout(() => {
  let isModal = !!document.querySelector("body.modal");
  if (isModal) return;
  let reloading = false;

  // dont load in iframes
  if (window.self !== window.top) return;

  // timeout after file change was detected
  // you can set this to 5000 if you see multiple redirects
  // that means that something triggers another reload on reload
  // monitor the devtools console - it will show the detected file change
  let redirecttimeout = 0;

  let evtSource;
  let startStream = function () {
    let url = "./?rockfrontend-livereload=" + LiveReloadPage;
    evtSource = new EventSource(url, { withCredentials: true });
    evtSource.onmessage = function (event) {
      // changed means data starts with "File changed:"
      let changed = event.data.startsWith("File changed:");
      if (!changed) return;
      if (reloading) return;

      // save message to display on next page load
      localStorage.setItem("livereload-message", event.data);

      // check if the current tab is active
      // if not, we do not reload
      // this prevents multiple simultaneous reloads that can lead to errors
      // when using RockMigrations
      if (document.hidden) {
        console.log("tab is not visible - waiting for reload");
        return;
      }

      if (LiveReloadForce) {
        document
          .querySelectorAll(".InputfieldStateChanged")
          .forEach((input) => {
            input.classList.remove("InputfieldStateChanged");
          });
      } else {
        // check if we are in the admin and have unsaved changes xx
        if (document.querySelectorAll(".InputfieldStateChanged").length) {
          console.log("detected change - unsaved changes prevent reload");
          // show notification
          // delay of 200ms prevents that the notification is shown
          // when a page is saved that creates files in the background
          setTimeout(() => {
            UIkit.notification({
              message:
                "Unsaved changes prevent reload - use $config->livereloadForce to force reload.",
              status: "warning",
              pos: "top-center",
              timeout: 0,
            });
          }, 200);
          return;
        }
        if (document.querySelectorAll("#pw-panel-shade").length) {
          console.log("detected change - open panel prevents reload");
          UIkit.notification({
            message:
              "Open panel prevents reload - use $config->livereloadForce to force reload.",
            status: "warning",
            pos: "top-center",
            timeout: 0,
          });
          return;
        }
      }

      // all fine, reload page
      let cnt = localStorage.getItem("livereload-count") || 0;
      localStorage.setItem("livereload-count", ++cnt);
      console.log("detected change - reloading " + cnt);
      reloading = true;

      // close eventsource connection
      evtSource.close();

      // reload window on next tick
      setTimeout(() => {
        document.location.reload(true);
      }, redirecttimeout);
    };

    evtSource.onerror = function (event) {
      if (document.querySelector("#tracy-bs")) return;
      console.log("Error occurred in EventSource.");
    };

    // before window unload, close event source
    window.addEventListener("beforeunload", () => {
      evtSource.close();
    });
  };

  startStream();
  console.log("RockFrontend is listening for file changes ...");
}, 1000);
