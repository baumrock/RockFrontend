# Boxed layout with outer and inner box

## Variables

```less
@tmg-box-outer: 1920px; // outer box
@tmg-box-inner: 1440px; // inner box without padding
@tmg-box-padding: 20px; // inner box padding
```

This will result in the outer width of tmg-box-outer being 1920px and the outer width of tmg-box-inner 1440 + 20 + 20 = 1480px

The inner width of tmg-box-inner will be 1440px

## Explanation

By default the outer box is 1920px wide. This is intended to be the maximum
outer width of the design. Everything outside this container will be blank
or whatever color or background image/pattern you like. I like to use the
uikit card shadow (using uk-card-default on the outer box element).

The inner box is by default 1440px wide and inteded to be used in every section as inner
container to fit the sections content. Note that the INNER width is 1440px
and it will add 20px of spacing left and right, to make sure that content is
1440px wide on large screens but has 20px padding on screens < 1440px
