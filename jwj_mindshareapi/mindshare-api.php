<?php
/**
 * Plugin Name: Mindshare API and Code Review Assignment
 * Plugin URI: https://github.com/thejameswilliam/mindshare_code_review
 * Description: Hopefully this allows you to better understand my skill as a developer. Use [jwj_shareposts count="int" cat="slug1, slug2"] as the shortcode. :-)
 * Version: 0.0.6
 * Author: James W. Johnson
 * Author URI: http://www.thejameswilliam.com
 */
 


/*
|--------------------------------------------------------------------------
| ASSETS
|--------------------------------------------------------------------------
*/


add_action( 'wp_enqueue_scripts', 'jwj_class_enqueued_assets' );

//include our stylesheet
function jwj_class_enqueued_assets() {
	wp_register_style('jwj-mindshare-css-styles', plugin_dir_url( __FILE__ ) . '/styles/style.css', array(), '1.0', 'all');
    wp_enqueue_style('jwj-mindshare-css-styles'); // Enqueue it!
};


//and just for fun a super overused font
add_action('wp_footer', 'jwj_mindshare_add_fonts');
function jwj_mindshare_add_fonts() {
	echo '<link href="https://fonts.googleapis.com/css?family=Lobster" rel="stylesheet" type="text/css">';	
};



/*
|--------------------------------------------------------------------------
| SHORTCODES 
|	[jwj_shareposts count="int" cat="slug1, slug2"]
|--------------------------------------------------------------------------
*/

add_shortcode('jwj_shareposts', 'jwj_list_shareposts');

function jwj_list_shareposts($shortcode_atts){

//get data from the api
$mindshare_data = file_get_contents('https://mind.sh/are/wp-json/posts'); //get the good stuff
$ms_posts = json_decode($mindshare_data, false); //decode it and make an object


//we need the default shortcode parameter for 'cat' to be all the posts.
//So lets get all the post categories and add each slug to an array.
$post_cats = array();
foreach ($ms_posts as $ms_post) { 
	$categories = $ms_post->terms->category;
	
	foreach($categories as $category) { 
		array_push($post_cats, $category->slug);
	}
};


//set defaults for our shortcode using our $post_cats array for the default categories.
$shortcode_atts = shortcode_atts( array(
	'count' => '10',		 //we could make this super high to ensure we get everything, but we'll add apgination in v2.0
	'cat' => $post_cats, 

), $shortcode_atts );


//create variables with our shortcode parameters
//if our shortcode parameter is not an array, we need to make it one allowing multiple categories in the parameter
if(!is_array($shortcode_atts[ 'cat' ])) { 
	$shortcode_atts[ 'cat' ] = explode( ", ", $shortcode_atts[ 'cat' ] );
};

//these variables will help us limit how many times we loop through the posts
$max_posts = $shortcode_atts['count']; 
$post_count = 0;	 
		

//now we can loop through the data and display the posts
		foreach ($ms_posts as $ms_post) { 
			//but only loop through some according to $shortcode_atts['count'];
			if($max_posts > $post_count) { 
				
				
					//create variables for the items we're going to use for each post. 
					$categories = $ms_post->terms->category;
					
					//re-use our $post_cats array and make it specific for the posts that we're working with.
					$post_cats = array();
					foreach($categories as $category) { 
						array_push($post_cats, $category->slug);
					}
					
					//check category slugs against slugs in the shortcode parameter	 and display a post if at least one matches
					if(array_intersect($shortcode_atts["cat"], $post_cats)) { 
						$excerpt = $ms_post->excerpt;
						$categories = $ms_post->terms->category;
						$title = $ms_post->title; 
						$link = $ms_post->link; 
						//We could get as much post information as we'd like, but we'll stick to just this.
						//I'm making these simple to read varables to make the code easier to write and understand.
						
						//check if there is a featured image and if there is, assign some variables
						if (is_object($ms_post->featured_image)) { 
							$image_url = $ms_post->featured_image->link;
							$image_title = $ms_post->featured_image->title;
						} else {
							//if there is not featured image we want to make sure these variables are empty.
							unset($image_url, $image_title);
						}
						
						
						
						// Start outputting
						$output = '<div class="jwj_title">';
						$output .= '<a href="' . $link . '">';
						$output .= $title;
						$output .= '</a>';
						$output .= '</div>';
						
						//display each category in a cute little box
						$output .= '<div class="jwj_categories">';
						foreach($categories as $category) { 
							$cat_link = $category->link;
							$output .= '<a href="' . $cat_link . '">';
							$output .= '<div class="jwj_category">' . $category->name . '</div>';
							$output .= '</a>';
						}	
						$output .= '</div>';
						
						//if we have an image, display it
						if(isset($image_url)) {
							$output .= '<div class="jwj_image">';
							$output .= '<img src="' . $image_url . '">';
							$output .= '</div>';
						};
						
						//it's unlikely a post wont have an excerpt in wordpress but we'll check anyway
						if(isset($excerpt)) {
							$output .= '<div class="jwj_excerpt">';
							$output .= $excerpt;
							$output .= '</div>';
						}
						//for nice cross theme compatability and layout
						$output .= '<hr class="jwj_clear">';
						
						echo $output; //put the post on the page
						$post_count++; //add to our post count
					
					// throw an error if there are no posts in that category
					} else {// throw an error on the page if there are no posts in that category
						echo 'That category doesnt seem to exist.';
						break;
					}; //end if category exists in shortcode
					
			// if the user wants 0 posts, then we should do nothing.	
				} elseif($max_posts <= 0) {
					echo 'Silence is golden.';
					break;
				}; //end if $max_posts > $post_count
				
		}; //end our foreach loop for the $ms_posts
	
} //end of shortcode function
?>
