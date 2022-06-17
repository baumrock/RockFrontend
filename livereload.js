// live reloading via SSE
// we start listening for changes one second after page load
// this makes sure that the page does not reload immediately after creating a new page
setTimeout(() => {
  console.log('RockFrontend livereload is listening for file changes...');
  const evtSource = new EventSource('/livereload.php', { withCredentials: true } );
  evtSource.onmessage = function(event) {
    let changes = JSON.parse(event.data);
    if(changes.length) {
      // check if we are in the admin and have unsaved changes
      let changes = document.querySelectorAll('.InputfieldStateChanged').length;
      if(!changes) document.location.reload(true);
    }
    else console.log('no change');
  }
}, 1000);
