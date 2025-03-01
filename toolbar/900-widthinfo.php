<?php if (!$user->isSuperuser()) return ?>
<style>
  #toolbar-widthinfo .breakpoints {
    display: inline-flex;
  }

  #toolbar-widthinfo .breakpoints>* {
    display: none;
  }

  #toolbar-widthinfo {
    display: flex;
    align-items: center;
  }
</style>
<span id='toolbar-widthinfo'>
  <small></small>
  <span class='breakpoints'>
    <small class='!inline-flex xs:w-2'></small>
    <small class='font-bold xs:!inline-flex sm:!hidden'>xs</small>
    <small class='font-bold sm:!inline-flex md:!hidden'>sm</small>
    <small class='font-bold md:!inline-flex lg:!hidden'>md</small>
    <small class='font-bold lg:!inline-flex xl:!hidden'>lg</small>
    <small class='font-bold xl:!inline-flex'>xl</small>
  </span>
</span>
<script>
  // write current width to <small> in #toolbar-widthinfo
  document.addEventListener('DOMContentLoaded', function() {
    const updateWidth = () => {
      const el = document.querySelector('#toolbar-widthinfo small');
      el.textContent = window.innerWidth + "px";
    };
    window.addEventListener('resize', updateWidth);
    updateWidth();
  });
</script>