=== Plugin Name ===
Contributors: Henry Kellner
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=UA33BJ78VQ48W&lc=US&item_name=Henry%20Kellner&item_number=hk_exif_tags&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: exif, images, tags, photos, photographs, photoblog
Requires at least: 3.2
Tested up to: 3.4.2
Stable tag: 1.12

"HK EXIF Tags" just adds a line below each image in each post with the EXIF informations.
Example: NIKON D600 (18mm, f/2.8, 1/60 sec, ISO200)

== Description ==

This plugin is very compact and simple, it just adds two hooks.
One to store the extra exif tag "make" in the database, which is missing in the standard word press database.
The database format itself will not be touched!
The manufacturer of the camera will only be visible on new uploaded images.

The second hook parses all posts before sending it to the browser, looking for images and adds just one
html tag (<span>) with the line of EXIF information.

To suppress the line with the EXIF information, add "hk_noexif" anywhere in the &lt;img&gt; tag like 
 &lt;img class="hk_noexif …"…&gt;

== Installation ==

1. Upload the hk_exif_tags.php file to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How can i change the style of the new line?

Just go to edit plugin in your admin panel. And find the lines:

        // ****************************************************************************************************************************
        // **
        // ** the follwing code defines the layout of the inserted line
        // **
        // ****************************************************************************************************************************
        
        $result = $matches[0] . '<br><span style="color:#888; font-size:small; font-weight:normal;">';
        $result = $result . $pmake . ' ' . $pcamera . ' (' . $pfocal_length . ', ' . $paperature . ', ' . $pshutter . ', ' . $piso . ')';
        $result = $result . '<br></span>';
        
        // ****************************************************************************************************************************

With basic html and php knowledge, you can change here the looking of the inserted line.

= Why is the manufacturer of the camera not visible?

The "make" EXIF-tag is not imported in the database by word press. After activating this plugin, also the make tag is imported in the database. The manufacturer will be visible below all new uploaded photos.

== Screenshots ==

1. This is how it looks like

== Changelog ==

= 1.0 = First stable release
= 1.1 = readme.xt updated
= 1.2 = screenshot added
= 1.3 = screenshot changed
= 1.4 = support for themes which have no link on photos
= 1.5 = added &lt;br&gt; tag for better positioning
= 1.6 = FIX: check if each field exists
= 1.7 = support for themes which don`t include attachment id in class
= 1.8 = eliminate long make names like "NIKON CORPORATION"
eliminate duplicate brand names in make and model field, like "Canon Canon EOS 5D"
= 1.9 = if &lt;img&gt; tag contains "hk_noexif" than do nothing
= 1.10 = support themes which add extra url parameters to image urls
= 1.11 = eliminate long make names like "PENTAX RICOH IMAGING"
= 1.12 = security issue fixed

