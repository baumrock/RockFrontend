<header class="uk-background-muted" <?= alfred($page) ?>>

  <div class="uk-container">
    <nav class="uk-navbar-container uk-navbar-transparent">
      <div class="uk-container">
        <div uk-navbar>

          <div class="uk-navbar-left">
            <a class="uk-navbar-item uk-logo" href="/" aria-label="Back to Home">Logo</a>
          </div>

          <div class="uk-navbar-right">

            <ul class="uk-navbar-nav">
              <?php foreach ($pages->get(1)->children("include=hidden") as $item) : ?>
                <li>
                  <a href="<?= $item->url ?>"><?= $item->title ?></a>
                  <?php if ($item->numChildren()) : ?>
                    <div class="uk-navbar-dropdown">
                      <ul class="uk-nav uk-navbar-dropdown-nav">
                        <?php foreach ($item->children as $child) : ?>
                          <li><a href="<?= $child->url ?>"><?= $child->title ?></a></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>

          </div>

        </div>
      </div>
    </nav>
  </div>

</header>