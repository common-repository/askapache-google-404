=== AskApache Google 404 ===
Contributors: askapache
Donate link: https://www.askapache.com/about/donate/
Tags: google, 404, 404-1, 0-404, 0404, not-found, missing, lost, error, htaccess, ErrorDocument, notfound, ajax, search, seo, mistyped, redirect, notify, url, news, videos, images, blogs, optimized, askapache, admin, ajax, template, traffic, oops
Requires at least: 3.3
Tested up to: 4.8.2
Stable tag: 5.1.2


== Description ==

AskApache Google 404 is a sweet and simple plugin that takes over the handling of any HTTP Errors that your blog has from time to time.  The most common type of error is when a page cannot be found, due to a bad link, mistyped URL, etc.. So this plugin uses some AJAX code, Google Search API'S,  and a few tricks to display a very helpful and Search-Engine Optimized Error Page. The default displays Google Search Results for images, news, blogs, videos, web, custom search engine, and your own site. It also searches for part of the requested filename that was not found, but it attaches your domain to the search for SEO and greater results.

This new version also adds Adsense paying 404 pages

[See it Live](https://www.askapache.com/htaccess-wordpress-404-plugins-google?robotics=mod_rewrite) at [AskApache](https://www.askapache.com/)

Read the [.htaccess Tutorial](https://www.askapache.com/htaccess/ ".htaccess File Tutorial") for more information on the advanced error logs.


== Installation ==

This section describes how to install the plugin and get it working. https://www.askapache.com/seo/404-google-wordpress-plugin/

1. Upload the zip file to the /wp-content/plugins/ directory and unzip.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to your Options Panel and open the "AA Google 404" submenu. /wp-admin/options-general.php?page=askapache-google-404.php
4. Configure your settings.
5. If you use a 404.php file, add <?php if(function_exists('aa_google_404'))aa_google_404();?> to the body.


== Frequently Asked Questions ==

Do I need a Google Account?

No.

Do I need a 404.php template file?

No, one is included with the plugin.

My 404.php page isn't being served for 404 Not Found errors!?

Add this to your [.htaccess file](https://www.askapache.com/htaccess/ "AskApache .htaccess File Tutorial") -- and read my [.htaccess Tutorial](https://www.askapache.com/htaccess/ "AskApache .htaccess File Tutorial") for more information.

ErrorDocument 404 /index.php?error=404
Redirect 404 /index.php?error=404

Fixing Status Headers

For super-advanced users, or those with access and knowledge of [Apache .htaccess/httpd.conf files](https://www.askapache.com/htaccess/ "AskApache .htaccess File Tutorial") you should check that your error pages are correctly returning a [404 Not Found HTTP Header](https://www.askapache.com/htaccess/apache-status-code-headers-errordocument/ "404 Not Found HTTP Header") and not a 200 OK Header which appears to be the default for many WP installs, this plugin attempts to fix this using PHP, but the best way is to use my .htaccess trick above.  You can check your headers by requesting a bad url on your site using my [online Advanced HTTP Header Tool](https://www.askapache.com/online-tools/http-headers-tool/ "HTTP Header Viewer").


== Other Notes ==

Future Awesomeness

The goal of this plugin is to boost your sites SEO by telling search engines to ignore your error pages, with the focus on human users to increase people staying on your site and being able to find what they were originally looking for on your site. Because I am obsessed with fast web pages, many various speed/efficiency improvements are also on the horizon.

Another feature that I am using with beta versions of this plugin, is tracking information for you to go over at your leisure, to fix recurring problems. The information is collected is the requested url that wasnt found, the referring url that contains the invalid link.

The reason I didnt include it in this release is because for sites like AskApache with a very high volume of traffic (and thus 404 requests) this feature can create a bottleneck and slow down or freeze a blog if thousands of 404 errors are being requested and saved to the database. This could also very quickly be used by malicious entities as a Denial of Service attack. So I am figuring out and putting into place limits.. like once a specific requested url resulting in a not found error has been requested 100x in a day, an email is sent to the blog administrator. But to prevent Email DoS and similar problems with the number and interval of emails allowed by your email provider other considerations on limits need to be examined.


== Screenshots ==

1. Basic AskApache 404 Look
2. Related Links Feature
3. Configuration Panel
4. New 404 Google Helper
