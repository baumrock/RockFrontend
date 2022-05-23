<div style='padding:40px;border:2px solid red;' <?= alfred($page) ?>>
  <?= __FILE__ ?>
  <p class='note'>
    Try hovering over this block. You should see icons on the top right corner to edit the current page.<br>
    <?php
    if(!$user->isLoggedin()) echo "<strong>You must log in to see this!</strong>";
    else echo "<strong>Of course this is only visible when logged in!</strong>";
    ?>
</div>
