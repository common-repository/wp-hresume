=== WP-hResume ===
Contributors: mithra62
Tags: stackoverflow, linkedin, resume, hresume, microformat, cv
Requires at least: 2.0.0
Tested up to: 2.9.1
Stable tag: trunk

Include any hResume encoded resume in any Wordpress page and style it how you like including Stack Overflow Careers and LinkedIn.

== Description ==

WP-hResume takes any hResume encoded webpage and allows you to embed it in your wordpress site. 

== Installation ==

1. Create backup.
2. Upload the zip file to the `/wp-content/plugins/` directory
3. Unzip.
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Create a new Page in the Wordpress admin and add the below example "shortcode" as the content.  
6. Optionally, create a new template page 

Please let me know any bugs, improvements, comments, suggestions.

**Example**

[wphres url="http://careers.stackoverflow.com/ericlamb" caching="off"]<br />
This will display my Stack Overflow resume with caching turned off.

[wphres url="http://www.linkedin.com/in/mithra62" caching="off"]<br />
This will display my LinkedIn resume with caching turned off.

It is HIGHLY recommended that caching is turned ON in a production site so you don't get blocked by Jeff Atwood (specifically).

== Screenshots ==

The following are live demos:

* [Eric Lamb](http://blog.ericlamb.net/resume/)

== Customization ==

The entire resume is marked up using CSS for cusom styling.

== Changelog ==

= 1.0 - 2010-01-25 =

* First release.
