// live reloading via SSE
// we start listening for changes one second after page load
// this makes sure that the page does not reload immediately after creating a new page
setTimeout(() => {
  console.log('RockFrontend is listening for file changes...');
  const evtSource = new EventSource(
    '/livereload.php?secret='+rf_livereload_secret,
    { withCredentials: true }
  );
  evtSource.onmessage = function(event) {
    let changes = JSON.parse(event.data);
    if(changes.length) {
      console.log(changes);
      // check if we are in the admin and have unsaved changes
      let editing = document.querySelectorAll('.InputfieldStateChanged').length;
      if(!editing) document.location.reload(true);
    }
    else console.log('no change');
  }
}, 1000);
