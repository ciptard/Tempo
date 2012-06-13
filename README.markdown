**Tempo: a simple PHP static site generator**

Tempo is a PHP script that uses PHP-based templates, a bunch of text files
and some images to build a static HTML site.

**Features**

- static pages
- blog with archive listing and RSS
- photo gallery with automatic image resizing
- supports Markdown syntax

**Installation**

You can put tempo.php and markdown.php anywhere on your system.

**Requirements**

You need PHP 5.x. Macs have it by default, on Linux it also comes
by default or it is very easy to install and on Windows you are
on your own.

**Setup**

Best way to learn is to check out the example site and go from there.
// TODO: make an example site :)

The source for a Tempo site might look like this:

		tempo.php
		markdown.php
		www.example.com/
			config/
				tempo-config.php
			media/
				image.jpg
				styles.css
			pages/
				index.txt
				about.txt
				blog-2012-06-15-title of blog post.txt
				blog-2012-06-17-another blog post.txt
			templates/
				header.php
				footer.php
				basepage.php
				blog-rss.php
				blog.php
			gallery/
				photos1/
					photo1.jpg
					photo2.jpg
					photo3!.jpg
				photos2/
					photo1.jpg
					photo2!.jpg
					photo3.jpg
			.htaccess

- tempo-config.php is the key file for configuring the site
- pages/*.txt get converted to *.html
- blog-2012-06-15-title of blog post.txt gets converted to blog/2012/06/15/title-of-blog-post.html
- media/* gets copied as-is
- gallery/* doesn't get copied but new cache/ in output folder will contain resized versions with same structure
- .htaccess is optional

**Usage**

	> cd MySites
	> ls
	www.example.com
	> ls -la www.example.com
	config
	gallery
	media
	output
	pages
	templates
	.htaccess
	> php /path/to/tempo.php www.example.com
	> ls -la www.example.com/output
	gallery
	media
	.htaccess
	about.html
	gallery.html
	index.html

	Use your favorite FTP or SSH client to copy www.example.com/output to your server.

	I like using rsync.

** Sites that use Tempo **

- http://www.catnapgames.com
- http://www.cattleshow.net
- http://www.skolkajitrenka.cz
