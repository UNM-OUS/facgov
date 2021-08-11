# Digraph Project Template

Composer project for building Digraph sites in the recommended fashion. Get started with `composer create-project digraphcms/digraph-project` 

## File/folder structure

Once you run `composer update` you will have the following skeleton of files and directories:

*  `digraph/` -- The root directory of where Digraph will keep all of its core source code, Composer-tracked modules, and cache/storage. The contents of this site will be largely untracked, as they are managed by Composer.

*  `modules/` -- A directory where you can write modules that are only needed in this particular site. The contents of this directory will be tracked.

* `media/` -- A directory where you can add your own media files to be served through Digraph. Any files placed here will be available as if `media/` were the web root. CSS and JS will also receive additional processing, such as allowing the importing of other files, minification. CSS files are also processed using CSS-Crush, so many additional features are available.

* `templates/` -- A directory where you can place Twig templates to create your own or override any of the built-in or module-provided templates.

* `web/` -- A directory containing the index.php file and accompanying .htaccess file that will create the primary point of entry for your site. For security you should point the web root of your domain at this directory, placing everything else outside the web root.

## Database setup

By default Digraph will keep its data in SQlite databases in `digraph/storage`. This behavior can be changed, but doing so is outside the scope of this guide.

SQLite performance is perfectly adequate for most sites, and you should really only consider switching to a more complex database setup once your site contains several thousand pages, or is structured in a fashion that is especially inefficient given the way Digraph stores and retrieves data.
