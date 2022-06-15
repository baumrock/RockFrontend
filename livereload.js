// live reloading via SSE
const evtSource = new EventSource('/livereload.php', { withCredentials: true } );
evtSource.onmessage = function(event) {
  let changes = JSON.parse(event.data);
  if(changes.length) {
    // check if we are in the admin and have unsaved changes
    let changes = document.querySelectorAll('.InputfieldStateChanged').length;
    if(!changes) document.location.reload(true);
    else console.log('changes on page prevent reload');
  }
  else console.log('rockfrontend live reload - no change');
}
