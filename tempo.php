<?php 

/*
Tempo 1.3
Author: Tomas Andrle
Website: http://www.catnapgames.com/blog/2011/10/13/tempo-php-static-site-generator.html

New in 1.3:

Photo galleries.

New in 1.2:

Add a check for the short_open_tag setting (must be being enabled). Add a sanity check
to see if the site config file was read.

New in 1.1:

Markdown support. Instead of the [img ] tags and HTML you can use Markdown. Just specify
"format markdown" in the text file header.
*/

require_once 'markdown.php';
// get markdown.php from http://michelf.com/projects/php-markdown/
// or mirror at http://www.catnapgames.com/media/src/markdown.php


function TempoResizeImage( $site_dir, $media_dir, $cache_dir, $output_dir, $filename, $width, $force_copy = false) {
    $height = 8000;

    list($width_orig, $height_orig) = getimagesize($filename);

	$info = pathinfo($filename);
	$dir = str_replace( "${site_dir}/${media_dir}", "${output_dir}/${cache_dir}", $info['dirname'] );

    if ( $width != 'original' && ( $width_orig > $width || $height_orig > $height ) ) {

        $ratio_orig = $width_orig/$height_orig;

        if ($width/$height > $ratio_orig) {
           $width = $height*$ratio_orig;
        } else {
           $height = round($width/$ratio_orig);
        }

        echo "  Resizing image $filename to ${width}x${height}\n";

        $output_filename = $dir .'/' . TempoStem($filename) . '-' . $width . '.'. $info['extension'];
        
		@mkdir( dirname( $output_filename ), 0777, true ); // recursive

        $image_p = imagecreatetruecolor($width, $height);
        if ( $info['extension'] == 'jpg' ) {
	        $image = imagecreatefromjpeg($filename);
	    } else {
	    	$image = iamgecreatefrompng($filename);
	    }
	    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                
        if ( $info['extension'] == 'jpg' ) {
            imagejpeg($image_p, $output_filename, 85);
        } else {
            imagepng( $image_p, $output_filename );
        }
                
        return $output_filename;
    } else {
    	if ( $force_copy ) {
    		$dest = $dir . basename( $filename );
    		echo "  Copying unchanged image to $dest\n";
    		copy( $filename, $dest );
    		return $dest;
    	} else {
	        return $filename;
	    }
    }
}

// recursively remove a dir. from comment at http://php.net/manual/en/function.copy.php
function TempoRmDir($dir) {
  if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
	    if ($file != "." && $file != "..") {
	    	TempoRmDir("$dir/$file");
	    }
	}
	rmdir($dir);
  } else {
  	if (file_exists($dir)) {
  		unlink($dir);
  	}
  }	
} 

// from comment at http://php.net/manual/en/function.copy.php
function TempoRCopy($src, $dst) {
	if (file_exists($dst)) {
		TempoRmDir($dst);
	}
	if (is_dir($src)) {
		mkdir($dst);
		$files = scandir($src);
		foreach ($files as $file) {
			if ($file != "." && $file != "..") {
				TempoRCopy("$src/$file", "$dst/$file"); 
			}
		}
	}
	else if (file_exists($src)) {
		copy($src, $dst);
	}
}

function TempoEndsWith($string, $end) {
    $strlen = strlen($string);
    $testlen = strlen($end);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $end, -$testlen, $testlen, true) === 0;
}

function TempoStem( $file ) {
    $info = pathinfo($file);
    $file_name =  basename($file,'.'.$info['extension']);
    return $file_name;
}

function TempoSlug( $string, $extension = '' ) {
    return strtolower( preg_replace( array( '/-/', '/[^a-zA-Z0-9\s\/\.]/', '/[\s]/', '/\.txt$/' ), array( '/', '', '-', '.'.$extension ), $string ) );
}

// lists txt files in a directory. optionally skips filenames that start with #.
function TempoListFiles( $path, $extension, $skip_hash = false ) {
    $dh = @opendir( $path );
    
    if ( !$dh ) {
    	return;
    } 

	$list = array();

    while( false !== ( $file = readdir( $dh ) ) ) {
		if ( !is_dir( "$path/$file" ) && TempoEndsWith( $file, $extension ) && ( !$skip_hash || ( strpos($file, '#' ) !== 0 ) ) ) {
			$list[] = $file;
		}
    }

    closedir( $dh );
    
    return $list;
}


function TempoListSubdirs( $path ) {
    $dh = @opendir( $path );

    if ( !$dh ) {
    	return;
    }

	$list = array();

    while( false !== ( $file = readdir( $dh ) ) ) {
		if ( is_dir( "$path/$file" ) && $file != '.' && $file != '..' ) {
			$list[] = $file;
		}
    }
    
    closedir( $dh );
    
    return $list;
}


function TempoFilterUrl( $var ) {
	$pattern = '/([^\"])(http:\/\/)([\w\.\/\-\:\+;_?=&%@$#~]*[\w\/\-\+;_?=&@%$#~])([\s]?)/';
	
	if ( preg_match_all( $pattern, $var, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		$offset_shift = 0;
		for ( $i=0; $i < count( $matches ); $i++ ) {
			$original = $matches[$i][0][0];
			$offset = $matches[$i][0][1] + $offset_shift;
			$part0 = $matches[$i][1][0]; // stuff just before the url; anything but " (which means it's probably already inside an <a src=".."> tag.
			$part1 = $matches[$i][2][0]; // http://
			$part2 = $matches[$i][3][0]; // www.something.com/path/path/
			$part3 = $matches[$i][4][0]; // stuff that's just behind the url but probably not part of it

			$link = $part0.'<a class="external" href="'.$part1.htmlspecialchars($part2).'" target="_blank">'.$part2.'</a>'.$part3;
			
			$var = substr_replace( $var, $link, $offset, strlen( $original ) );
			$offset_shift += strlen( $link ) - strlen( $original );
		}
		return $var;
	} else {
		return $var;
	}
}

function TempoFilterImg( $site_dir, $site_url, $image_dimensions, $output_dir, $var, $root_url ) {
	$href = '$2.$3';
	$pattern = '/\[(imgleft|imgright|imgfull)\s+([^\]]*)\]/'; // example: [imgleft media/123.png]
	$matches = array();
	if ( preg_match_all( $pattern, $var, $matches, PREG_SET_ORDER ) ) {
		for ( $i=0; $i < count( $matches ); $i++ ) {
			$placement = $matches[$i][1];
			$src = $matches[$i][2];
			
			$width = $image_dimensions[ $placement ];
			$new = TempoResizeImage( $site_dir, $media_dir, $cache_dir, $output_dir, $site_dir . '/' . $src, $width );
			$new = str_replace( $output_dir .'/', '', $new ); // image was resized, links to thumbnail
			$new = str_replace( $site_dir .'/', '', $new ); // image was not resized, links to original
			
			$new = $root_url . $new;
			
			$replacement = '<img class="'.$placement.'" alt="" src="'.$new.'"/>'; 
			$var = preg_replace( $pattern, $replacement, $var, 1 );
		}
	}
	return $var;
}

function TempoGeneratePage( $template_dir, $site_url, $blog_rss, $blog_files, $item ) {
	extract( $item );
	
	$template_file = $template_dir . '/' . $template . '.php';	
	$template_code = file_get_contents( $template_file );

	ob_start();
	eval( '?>' . $template_code );
	$contents = ob_get_contents();
	ob_end_clean();
	
	return $contents;
}

function TempoParse( $default_format, $site_url, $file, $lines ) {
	$reading_header = true;
	$body = array();
	$result = array();
	$result['menuitem'] = '';
	$result['extension'] = 'html';

	foreach ( $lines as $line ) {
		if ( $reading_header ) {
			$line = trim( $line );
			$matches = array();
			if ( preg_match( "/^(rss|extension|template|format|title|menuitem)\s+(.+)$/", $line, $matches ) ) {
				$result[ $matches[ 1 ] ] = $matches[ 2 ];
			}
			
			if ( $line == '--' ) {
				$reading_header = false;
			}
		} else {
			// accumulate into buffer
			$body[] = rtrim($line);
		}
	}
	
	$result['slug'] = TempoSlug( $file, $result['extension'] );
	$result['url'] = $site_url . '/' . $result[ 'slug' ];
	$result['rss'] = in_array( strtolower($result['rss']), array( 'yes', 'true', 'on', 'enable', 'enabled' ) );

	if ( $result['rss'] ) {
		echo "  XML feed mode enabled\n";
		$result['root_url'] = $site_url . '/';
	} else {
		$num_parent_dirs = count( explode( '/', $result['slug'] ) ) - 1;
		$result['root_url'] = str_repeat( '../', $num_parent_dirs );
	}
	
	$result['body'] = join( $body, "\n" );
	
	if ( !$result['format'] ) {
		$result['format'] = $default_format;
	}
	
	switch ( $result['format'] ) {
		case 'markdown':
			$result['body'] = Markdown( $result['body'] );
			break;
			
		case 'tempo':
			$result['body'] = TempoFilterUrl( $result['body'] );
			$result['body'] = TempoFilterImg( $site_dir, $site_url, $image_dimensions, $output_dir, $result['body'], $result['root_url'] );
			
		default:
			// no filtering
	}

	return $result;
}


function TempoBeautifyName( $s ) {
	return ucwords( str_replace( '-', ' ', str_replace( '_', ' ', $s ) ) );
}


function TempoMain( $argc, $argv ) {
	if ( $argc != 2 ) {
		echo "Usage: tempo.php site_directory\n";
		exit(0);
	}
	
	if ( !ini_get( 'short_open_tag' ) ) {
		if ( !ini_set( 'short_open_tag', '1' ) ) {
			die( "PHP setting 'short_open_tag' is disabled and cannot be enabled from inside this script. Please enable it in your php.ini file and try again" );
		}
	}
	
	chdir( $argv[1] . '/..' );
	
	$config_filename = $argv[1].'/config/tempo-config.php';
	require_once $config_filename;
	extract( $config );
	
	if ( ( !$site_dir ) || ( !is_dir( $site_dir ) ) || ( !$output_dir ) ) {
		die( "Invalid config file. Please check that $config_filename contains all the required info." );
	}
	
	// delete previous version
	echo "Cleaning old output\n";
	TempoRmDir( $output_dir );
	
	// get list of pages
	function map_file_list( $i ) { return array( 'file' => $i ); }
	$all_files = array_map( 'map_file_list', TempoListFiles( $pages_dir, '.txt' ) );
	
	// parse titles, template names and other meta information, add it to $all_files
	for ( $i=0; $i < count( $all_files ); $i++ ) {
		extract( $all_files[ $i ] );
		$lines = file( $pages_dir . '/' . $file );
		$vars = TempoParse( $default_format, $site_url, $file, $lines );
		$all_files[ $i ] = array_merge( $all_files[ $i ], $vars );
	}
	
	// blog
	$blog_pattern = '/^'.$blog_prefix.'-(?P<year>[0-9]{4})-(?P<month>[0-9]{2})-(?P<day>[0-9]{2})-(.*)\.txt$/';
	function TempoBlogSortDescending($a, $b) { return strcmp($b["file"], $a["file"]); }
	
	// filter blog posts
	
	$blog_files = array();
	usort( $all_files, "TempoBlogSortDescending" );
	
	for ( $i=0; $i < count( $all_files ); $i++ ) {
		if ( preg_match( $blog_pattern, $all_files[ $i ]['file'] ) ) {
			$blog_files[] = $all_files[ $i ];
		}
	}
	
	// extract blog post dates (year, month, day), save back into $blog_files
	for ( $i=0; $i < count( $blog_files ); $i++ ) {
		$matches = array();
		preg_match( $blog_pattern, $blog_files[ $i ]['file'], $matches );
		$blog_files[ $i ] = array_merge( $blog_files[ $i ], $matches );
	}
	
	//$blog_latest = array_slice( $blog_files, 0, min( $blog_num_latest, count( $blog_files ) ) );
	$blog_rss = array_slice( $blog_files, 0, min( $blog_num_rss, count( $blog_files ) ) );
		
	// generate html for regular pages
	for ( $i=0; $i < count( $all_files ); $i++ ) {
		echo "Processing ".$all_files[ $i ]['file']."\n";
	
		$contents = TempoGeneratePage( $template_dir, $site_url, $blog_rss, $blog_files, $all_files[$i] );
		
		$destination = $output_dir . '/' . $all_files[$i]['slug'];
		
		@mkdir( dirname( $destination ), 0777, true );
		
		file_put_contents( $destination, $contents );
	}	
	
	// copy static resources
	echo "Copying media\n";
	TempoRCopy( $site_dir . '/'. $media_dir, $output_dir . '/'.$media_dir );
	TempoRCopy( $site_dir . '/.htaccess', $output_dir . '/.htaccess' );
	
	// generate html for galleries - without the 'pages' txt system, using php templates directly
	if ( $gallery_dir ) {

		$menuitem = $gallery_menuitem;

		$gallery_dirs = TempoListSubdirs( $site_dir . '/'. $gallery_dir );
		$galleries = array();
				
		foreach( $gallery_dirs as $dir ) {
			echo "Processing gallery $dir\n";
			
			$jpgs = TempoListFiles( $site_dir . '/'. $gallery_dir . '/' . $dir, '.jpg' );
			
			$gallery_slug = TempoSlug( str_replace( '-', ' ', $dir ) );
			
			// filename with '!' means this is the key photo to be used in the gallery index for a given gallery.
			$key_photo_index = 0;
			for ( $i=0; $i < count( $jpgs ); $i++ ) {
				if ( strpos( $jpgs[ $i ], '!' ) !== false ) {
					$key_photo_index = $i;
				}
			}
			
			$items = array();
			$index = 0;
			
			foreach( $jpgs as $jpg ) {			
				$path = $gallery_dir . '/' . $dir . '/' . $jpg; 
				
				if ( $gallery_thumb ) {
					$thumb = TempoResizeImage( $site_dir, $gallery_dir, $cache_dir, $output_dir, $site_dir . '/' . $path, $gallery_thumb, true );
					$thumb = str_replace( $output_dir .'/', '', $thumb ); // image was resized, links to thumbnail
					$thumb = str_replace( $site_dir .'/', '', $thumb ); // image was not resized, links to original
				}

				if ( $gallery_small ) {
					$small = TempoResizeImage( $site_dir, $gallery_dir, $cache_dir, $output_dir, $site_dir . '/' . $path, $gallery_small, true );
					$small = str_replace( $output_dir .'/', '', $small ); // image was resized, links to thumbnail
					$small = str_replace( $site_dir .'/', '', $small ); // image was not resized, links to original
				}

				if ( $gallery_large ) {
					$large = TempoResizeImage( $site_dir, $gallery_dir, $cache_dir, $output_dir, $site_dir . '/' . $path, $gallery_large, true );
					$large = str_replace( $output_dir .'/', '', $large ); // image was resized, links to thumbnail
					$large = str_replace( $site_dir .'/', '', $large ); // image was not resized, links to original
				}

				$detail_slug = str_pad( $index+1, 4, '0', STR_PAD_LEFT );
				$items[] = array(
					'thumb' => $thumb,
					'small' => $small,
					'large' => $large,
					'slug' => $detail_slug,
					'link' => $detail_slug  . '.html',
				);
				
				$index++;
			}
			
			$galleries[] = array(
				'items' => $items,
				'key_photo_index' => $key_photo_index,
				'count' => count( $items ),
				'slug' => $gallery_slug,
				'link' => $gallery_dir . '/' . $gallery_slug . '/index.html',
				'name' => TempoBeautifyName( $dir ), // any way to "beautify" ? capitalize, replace underscores?
			);
		}

		$gallery_index_template = $template_dir . '/gallery-index.php';
		$gallery_list_template = $template_dir . '/gallery-list.php';
		$gallery_detail_template = $template_dir . '/gallery-detail.php';

		// write gallery index html		
		$code = file_get_contents( $gallery_index_template );
	
		ob_start();
		eval( '?>' . $code );
		$contents = ob_get_contents();
		ob_end_clean();
	
		file_put_contents( $output_dir . '/' . $gallery_dir . '.html', $contents );
	
		// write listing html for each gallery
		if ( file_exists( $gallery_list_template ) ) {
			foreach( $galleries as $gallery ) {
				echo 'Generating HTML for gallery '.$gallery['name']."\n";
			
				$code = file_get_contents( $gallery_list_template );
				$root_url = '../../';
			
				ob_start();
				eval( '?>' . $code );
				$contents = ob_get_contents();
				ob_end_clean();
			
				$dir = $output_dir . '/' . $gallery_dir . '/' . $gallery['slug'];
				@mkdir( $dir, 0777, true ); // true=recursive
				file_put_contents( $dir . '/index.html', $contents );
				
				if ( file_exists( $gallery_detail_template ) ) {
					foreach( $gallery['items'] as $item ) {
						echo 'Generating HTML for gallery item '.$item['detail_slug'] . "\n";
						
						$code = file_get_contents( $gallery_detail_template );
						$root_url = '../../';
					
						ob_start();
						eval( '?>' . $code );
						$contents = ob_get_contents();
						ob_end_clean();
					
						$dir = $output_dir . '/' . $gallery_dir . '/' . $gallery['slug'];
						file_put_contents( $dir . '/' . $item['slug'] . '.html', $contents );
					}
				}
			}
		}	
	}	
}


TempoMain( $argc, $argv );


?>