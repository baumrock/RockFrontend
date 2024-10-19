// live reloading via SSE
// we start listening for changes one second after page load
// this makes sure that the page does not reload immediately after creating a new page
setTimeout(() => {
  let isModal = !!document.querySelector("body.modal");
  if (isModal) return;
  let reloading = false;
  let warningShown = false;

  // dont load in iframes
  if (window.self !== window.top) return;

  // timeout after file change was detected
  // you can set this to 5000 if you see multiple redirects
  // that means that something triggers another reload on reload
  // monitor the devtools console - it will show the detected file change
  let redirecttimeout = 0;

  let evtSource;
  let startStream = function () {
    let url = LiveReloadUrl + "?rockfrontend-livereload=" + LiveReloadPage;
    evtSource = new EventSource(url, { withCredentials: true });
    evtSource.onmessage = function (event) {
      let changed = event.data;
      if (!changed) return;
      if (reloading) return;

      // show changed file
      console.log(changed);

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
        // check if we are in the admin and have unsaved changes
        if (document.querySelectorAll(".InputfieldStateChanged").length) {
          if (!warningShown) {
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
            warningShown = true;
          }
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
      setTimeout(() => {
        document.location.reload(true);
      }, redirecttimeout);
    };

    evtSource.onerror = function (event) {
      if (document.querySelector("#tracy-bs")) return;
      console.log("Error occurred in EventSource.");
    };
  };

  startStream();
  console.log("RockFrontend is listening for file changes...");
}, 1000);
