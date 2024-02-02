<div id="rf-topbar" style="z-index: <?= $z ?>">

  <?= $rf->topbarPrependMarkup ?>

  <a href="<?= $pages->get(2)->url ?>module/edit?name=RockFrontend">
    <img id="rf-logo" src="<?= $logourl ?>">
  </a>

  <a href="<?= $pages->get(2)->url ?>" title="Go to pagetree" uk-tooltip>
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
      <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
        <rect width="6" height="6" x="3" y="15" rx="2" />
        <rect width="6" height="6" x="15" y="15" rx="2" />
        <rect width="6" height="6" x="9" y="3" rx="2" />
        <path d="M6 15v-1a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1m-6-6v3" />
      </g>
    </svg>
  </a>

  <a href="<?= $page->editUrl() ?>" title="Edit this page" uk-tooltip>
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
      <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
        <path d="M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1" />
        <path d="M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97L9 12v3h3l8.385-8.415zM16 5l3 3" />
      </g>
    </svg>
  </a>

  <?php if ($modules->isInstalled("RockPageBuilder")) : ?>
    <a href="#" class="rf-toggle-sortable" title="Toggle Drag&Drop" uk-tooltip>
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
          <path d="M19 11V9a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" />
          <path d="m13 13l9 3l-4 2l-2 4l-3-9M3 3v.01M7 3v.01M11 3v.01M15 3v.01M3 7v.01M3 11v.01M3 15v.01" />
        </g>
      </svg>
    </a>
  <?php endif; ?>

  <a href="#" class="rf-topbar-hide" title="Hide topbar" uk-tooltip>
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
      <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
        <path d="m3 3l18 18M10.584 10.587a2 2 0 0 0 2.828 2.83" />
        <path d="M9.363 5.365A9.466 9.466 0 0 1 12 5c4 0 7.333 2.333 10 7c-.778 1.361-1.612 2.524-2.503 3.488m-2.14 1.861C15.726 18.449 13.942 19 12 19c-4 0-7.333-2.333-10-7c1.369-2.395 2.913-4.175 4.632-5.341" />
      </g>
    </svg>
  </a>

  <a href=/ class="rf-device-preview" title="Responsive Preview" uk-tooltip>
    <svg width="100%" height="100%" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;">
      <g>
        <g transform="matrix(1,0,0,1,-1,0)">
          <path d="M28,6.667C28,5.931 27.403,5.333 26.667,5.333L5.333,5.333C4.597,5.333 4,5.931 4,6.667L4,20C4,20.736 4.597,21.333 5.333,21.333L26.667,21.333C27.403,21.333 28,20.736 28,20L28,6.667Z" style="fill:white;stroke:black;stroke-width:2.67px;" />
        </g>
        <g transform="matrix(1,0,0,1,-1,0)">
          <path d="M9.333,26.667L22.667,26.667M12,21.333L12,26.667M20,21.333L20,26.667" style="fill:none;fill-rule:nonzero;stroke:black;stroke-width:2.67px;" />
        </g>
        <g transform="matrix(0.634367,0,0,0.634367,15.3738,11.1835)">
          <g transform="matrix(1,0,0,1,-1.57637,0)">
            <path d="M24,8.204C24,5.884 22.116,4 19.796,4L12.204,4C9.884,4 8,5.884 8,8.204L8,23.796C8,26.116 9.884,28 12.204,28L19.796,28C22.116,28 24,26.116 24,23.796L24,8.204Z" style="fill:white;stroke:black;stroke-width:2.67px;" />
          </g>
          <g transform="matrix(1,0,0,1,-1.57637,0)">
            <path d="M14.667,5.333L17.333,5.333M16,22.667L16,22.68" style="fill:white;fill-rule:nonzero;stroke:black;stroke-width:2.67px;" />
          </g>
        </g>
      </g>
    </svg>
  </a>

  <?= $rf->topbarAppendMarkup ?>

  <?php if ($user->isSuperuser()) : ?>
    <span style="margin-left: 10px;" title="Edit Template" uk-tooltip>[
      <a href="<?= $pages->get(2)->url ?>setup/template/edit?id=<?= $page->template->id ?>">
        <?= $page->template->name ?>
      </a>
      ]</span>
  <?php endif; ?>
</div>
<div id="rf-device-preview">
  <div class="iframe-wrapper">
    <div></div>
    <div class="rf-devices">
      <a href=/ class="rf-close">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
          <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12" />
        </svg>
      </a>
    </div>
    <div>
      <iframe name="rf-preview" data-src="<?= $this->wire->input->url ?>">
      </iframe>
    </div>
    <div></div>
  </div>
</div>
<script>
  (function() {
    let $bar = document.querySelector("#rf-topbar");
    let $preview = document.querySelector("#rf-device-preview");

    // we are in the rockfrontend preview iframe
    if (window.name === 'rf-preview') {
      document.querySelector('body').classList.add('rf-preview');
    }

    // listen to clicks
    document.addEventListener('click', function(event) {
      let body = document.querySelector("body");

      // show topbar
      if (event.target.matches('#rf-topbar')) {
        event.preventDefault();
        event.target.classList.remove('hide');
        localStorage.setItem('rf-topbar-hide', 0);
        body.classList.remove("no-alfred");
      }

      let $a = event.target.closest('a');
      if (!$a) return;
      if ($a.matches('.rf-device-preview')) {
        // click on devices icon in bar
        event.preventDefault();
        $preview.classList.toggle('show');
        document.querySelector('body').classList.toggle('overflow-hidden');
        let $iframe = $preview.querySelector('iframe');
        $iframe.setAttribute('src', $iframe.dataset.src);
      } else if ($a.matches('.rf-close')) {
        // close preview modal
        event.preventDefault();
        $preview.classList.remove('show');
        document.querySelector('body').classList.remove('overflow-hidden');
      } else if ($a.matches('.rf-topbar-hide')) {
        // hide topbar
        event.preventDefault();
        $a.closest('#rf-topbar').classList.add('hide');
        localStorage.setItem('rf-topbar-hide', 1);
        body.classList.add("no-alfred");
      } else if ($a.matches('.rf-toggle-sortable')) {
        // toggle sortable
        event.preventDefault();
        if (typeof RockSortable !== 'undefined') RockSortable.toggle();
      }
    }, false);

    // initial state of topbar
    if (localStorage.getItem('rf-topbar-hide') == 1) {
      document.querySelector('.rf-topbar-hide').click();
    }
  })()
</script>