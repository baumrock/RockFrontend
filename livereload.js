// live reloading via SSE
const evtSource = new EventSource('/livereload.php', { withCredentials: true } );
evtSource.onmessage = function(event) {
  let changes = JSON.parse(event.data);
  if(changes.length) document.location.reload(true);
  else console.log('rockfrontend live reload - no change');
}
