# Static Rendering

Static Rendering is a convenient feature offered by RockFrontend that simplifies the process of developing new websites or features. It provides an alternative approach to generating markup by allowing developers to write static markup for specific pages or components, rather than utilizing multiple includes and delving into the intricacies of the entire project's architecture.

## How it Works

If your project is already using RockFrontend's `renderLayout()` method to generate page layouts, integrating the Static Rendering feature is straightforward. All you need to do is create static markup files and place them within the designated folder: `/site/templates/static`.

When a user requests a particular page, RockFrontend's Static Rendering functionality kicks in. It examines the requested page's URL and checks whether there is a corresponding static file in the `/site/templates/static` directory that matches the URL's structure.

For example: If a user requests `your.site/foo/bar`, RockFrontend will search for a static file located at `/site/templates/static/foo/bar.[php|latte]`.

## Benefits

Static Rendering offers several benefits for developers, including:

- **Simplified Development:** Writing static markup can be more straightforward than dealing with complex includes and understanding the project's full architecture.
- **Quick Prototyping:** Static Rendering enables developers to quickly prototype ideas without the need to set up dynamic functionality.
- **Unique Custom Layouts:** With Static Rendering, you can craft highly distinctive layouts for individual pages, allowing you to create designs that stand out from the rest of the website and cater to specific visual and functional requirements.
- **Isolation:** Developers can work on specific pages or components in isolation without affecting the rest of the application.

## Implementation Steps

1. **Create Static Files:** Write the static markup for the desired pages or components and save them with filenames that match the URL structure. Place these files in the `/site/templates/static` directory.

2. **Access Static Rendered Pages:** Once the static files are in place, RockFrontend will automatically use them for rendering when a user accesses the corresponding URLs.