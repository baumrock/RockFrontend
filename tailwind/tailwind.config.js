/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./site/templates/**/*.{latte,php}"],
  theme: {
    extend: {
      screens: {
        xs: "480px",
        sm: "640px",
        md: "960px",
        lg: "1200px",
        xl: "1600px",
      },
    },
  },
  plugins: [],
  // disable preflight
  // this prevents tailwind from conflicting with uikit overrides
  // see https://tailwindcss.com/docs/preflight
  corePlugins: {
    preflight: false,
  },
};
