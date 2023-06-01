<p align="right"><a href="README-de.md">Deutsch</a> &nbsp; <a href="README.md">English</a> &nbsp; <a href="README-sv.md">Svenska</a></p>

# Check 0.8.1

Find broken links.

<p align="center"><img src="check-screenshot.png?raw=true" alt="Screenshot"></p>

## How to install an extension

[Download ZIP file](https://github.com/annaesvensson/yellow-check/archive/main.zip) and copy it into your `system/extensions` folder. [Learn more about extensions](https://github.com/annaesvensson/yellow-update).

## How to find broken links

You can find broken links at the [command line](https://github.com/annaesvensson/yellow-core). It is quite normal for pages to be renamed, files to be removed and links to no longer work. Don’t worry, it's not hard to find and fix broken links. Open a terminal window. Go to your installation folder, where the file `yellow.php` is. Type `php yellow.php check`, you can optionally add a folder and a location. This will find broken links and show them on screen.

If you don't want that a page is checked, set `Generate: exclude` in the [page settings](https://github.com/annaesvensson/yellow-core#settings-page) at the top of a page.

## How to fix broken links

Once you have found broken links on your website, it’s time to fix them. You have two options. First, replace broken links. Try to find the new location or the new URL. Second, remove broken links. If it's not possible to find a replacement link then there is nothing left but to remove the link.

## Examples

Content file with option for generating a static website:

    ---
    Title: Example page
    Generate: exclude
    ---
    This page is not checked for broken links.

Finding broken links at the command line:

`php yellow.php check`  

Finding broken links at the command line, different locations:

`php yellow.php check system/temporary /wiki/`  
`php yellow.php check system/temporary /blog/`  
`php yellow.php check system/temporary /help/how-to-make-a-small-website`  

## Acknowledgements

This extension uses [curl](https://github.com/curl/curl) by Daniel Stenberg. Thank you for the useful library.

## Developer

Anna Svensson. [Get help](https://datenstrom.se/yellow/help/).
