# Site modules directory

This directory is where modules that are not installed via Composer should be kept. If your site requires a one-off module that you won't be redistributing or using elsewhere, you can build it here and just track its source code in the site's repository.

If you're going to be using a module on more than one site you should *not* build it here. You should instead build it as a Composer package and install it with Composer.

## Debugging module

If you install the debug module here (`git clone git@gitlab.com:byjoby/digraph-debug-module.git`) it will be excluded by the default .gitignore file. This is to make using the debug module on development environments both easy and safer.
