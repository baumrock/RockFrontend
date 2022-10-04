# GridDropdown UIkit Component

Show "read more" dropdowns below items of a uikit grid:

<img src=https://i.imgur.com/epzTaHd.png height=300>

Note that nested grids are not supported!

## Usage

Just add the `rf-griddropdown` class to the grid where you want to use the dropdowns. Then add the `rf-griddropdown-toggle` to the element that toggles the dropdown and then add a hidden element directly after the toggle. This is the markup that will be used to populate the dropdown!

```html
<div class='rf-griddropdown' uk-grid>
  {foreach $items as $item}
    <div class='uk-width-1-3'>
      items visible content
      <a class='rf-griddropdown-toggle'>show info</a>
      <div class='uk-hidden'>
        <button class="uk-alert-close" type="button" uk-close></button>
        your dropdown markup here
      </div>
    </div>
  {/foreach}
</div>
```

## Arrows

To add an arrow to your dropdown that is aligned with the griditem simply add this markup to your dropdown:

```html
<div class='rf-griddropdown-arrow'>
  <svg><!-- custom svg arrow --></svg>
</div>
```

```css
.rf-griddropdown-arrow {
  margin-top: 20px;
  svg {
    height: 50px;
    margin-top: -45px;
  }
}
```
