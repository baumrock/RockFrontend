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
    <small class='font-bold xs:!inline-flex sm:!hidden'>&nbsp;&nbsp;xs</small>
    <small class='font-bold sm:!inline-flex md:!hidden'>&nbsp;&nbsp;sm</small>
    <small class='font-bold md:!inline-flex lg:!hidden'>&nbsp;&nbsp;md</small>
    <small class='font-bold lg:!inline-flex xl:!hidden'>&nbsp;&nbsp;lg</small>
    <small class='font-bold xl:!inline-flex'>&nbsp;&nbsp;xl</small>
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