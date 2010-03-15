Tiny dropbox script
-------------------
Written by Einar Lielmanis.
Released under the MIT license: http://www.opensource.org/licenses/mit-license.php 
Hack away! Bugs, thanks, suggestions: einar@bugpipe.org

Requirements

If it runs PHP, it will probably run tiny dropbox as well, regardless of the setup: the script is quite flexible.


Installation

Put the file into any folder of your web hosting provider.
By visiting the page the first time, you will automatically be logged in as owner
and will be able to customize language, password, etc.

If the script is unable to create the storage folder by itself, it will complain; in that case you will need to 
create the folder "files" and assign sufficient permissions manually.


Specific customizations

I doubt that you will need taht, but to allow your customizations together with simple upgrades of this script,
you can create a file called config.php and store any overrides there.
Here is an example of the file:

  <?php
  $g_storage_folder = '/var/storage';
  ?>

You can add your own interface languages (via add_language() function) or change the upload folder from the 
custom.php: everything else can be done from the owner settings page.

PHP upload limits

These values in php.ini limit how large files you will be able to upload:

  upload_max_filesize
  post_max_filesize
  memory_limit

