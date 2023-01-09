// live reloading via SSE
// we start listening for changes one second after page load
// this makes sure that the page does not reload immediately after creating a new page
setTimeout(() => {
  let isModal = !!document.querySelector("body.modal");
  if (isModal) return;
  let reloading = false;

  // timeout after file change was detected
  // you can set this to 5000 if you see multiple redirects
  // that means that something triggers another reload on reload
  // monitor the devtools console - it will show the detected file change
  let redirecttimeout = 0;

  let evtSource;
  let startStream = function () {
    let rf = RockFrontend;
    let url = rf.rootUrl + "?rockfrontend-livereload=" + rf.livereloadSecret;
    evtSource = new EventSource(url, { withCredentials: true });
    evtSource.onmessage = function (event) {
      let changed = event.data;
      if (changed && !reloading) {
        console.log(changed);
        // check if we are in the admin and have unsaved changes
        if (document.querySelectorAll(".InputfieldStateChanged").length) {
          console.log("detected change - unsaved changes prevent reload");
          UIkit.notification({
            message: "Unsaved changes prevent reload",
            status: "warning",
            pos: "top-center",
            timeout: 0,
          });
          return;
        }
        if (document.querySelectorAll("#pw-panel-shade").length) {
          console.log("detected change - open panel prevents reload");
          UIkit.notification({
            message: "Open panel prevents reload",
            status: "warning",
            pos: "top-center",
            timeout: 0,
          });
          return;
        }
        // all fine, reload page
        console.log("detected change - reloading");
        reloading = true;
        setTimeout(() => {
          document.location.reload(true);
        }, redirecttimeout);
      }
    };
  };

  startStream();
  console.log("RockFrontend is listening for file changes...");

  // this causes livereload to break if vscode links are clicked
  // TODO: find a better solution to fix the firefox issue
  // // prevent error msg in Firefox https://bugzilla.mozilla.org/show_bug.cgi?id=833462
  // window.addEventListener("beforeunload", () => {
  //   evtSource.close();
  //   setTimeout(() => {
  //     // we restart the stream because clicks on vscode links would
  //     // otherwise stop the livereload watcher stream!
  //     startStream();
  //   }, 50);
  // });
}, 1000);
