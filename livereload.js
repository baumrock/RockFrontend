// live reloading via SSE
// we start listening for changes one second after page load
// this makes sure that the page does not reload immediately after creating a new page
setTimeout(() => {
  let isModal = !!document.querySelector('body.modal');
  if(isModal) return;
  let reloading = false;

  let evtSource;
  let startStream = function() {
    evtSource = new EventSource(
      '/livereload.php?secret='+rf_livereload_secret,
      { withCredentials: true }
    );
    evtSource.onmessage = function(event) {
      let changes = JSON.parse(event.data);
      if(changes.length && !reloading) {
        console.log(changes);
        // check if we are in the admin and have unsaved changes
        if(document.querySelectorAll('.InputfieldStateChanged').length) {
          console.log('detected change - unsaved changes prevent reload');
          return;
        }
        if(document.querySelectorAll('#pw-panel-shade').length) {
          console.log('detected change - open panel prevents reload');
          return;
        }
        // all fine, reload page
        reloading = true;
        console.log('detected change - reloading', changes);
        document.location.reload(true);
      }
      else if(!reloading) console.log('no change');
    }
  }

  startStream();
  console.log('RockFrontend is listening for file changes...');

  // prevent error msg in Firefox https://bugzilla.mozilla.org/show_bug.cgi?id=833462
  window.addEventListener('beforeunload', () => {
    evtSource.close();
    setTimeout(() => {
      // we restart the stream because clicks on vscode links would
      // otherwise stop the livereload watcher stream!
      startStream();
    }, 50);
  });

}, 1000);
