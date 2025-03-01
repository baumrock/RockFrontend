<?php if (!$page->editable()) return; ?>
<a
  title="<?= $wire->_('Overlays on/off') ?>"
  uk-tooltip
  data-toggle="overlays"
  data-persist
  class='on'>
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2zM4 4v.01M8 4v.01M12 4v.01M16 4v.01M4 8v.01M4 12v.01M4 16v.01" />
  </svg>
</a>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    RockFrontendToolbar.onToggle('overlays', (type) => {
      if (type === 'off') {
        document.body.classList.add("no-alfred");
      } else {
        document.body.classList.remove("no-alfred");
      }
    });
  });
</script>