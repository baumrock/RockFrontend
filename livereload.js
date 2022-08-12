// live reloading via SSE
// we start listening for changes one second after page load
// this makes sure that the page does not reload immediately after creating a new page
setTimeout(() => {
  console.log('RockFrontend is listening for file changes...');
  let reloading = false;
  const evtSource = new EventSource(
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
}, 1000);
