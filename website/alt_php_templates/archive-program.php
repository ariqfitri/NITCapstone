   <?php 
    get_header(); 
    require_once 'config/db_config.php';
   ?>

   <div class="container">
       <div class="program-archive-header">
           <h1><?php post_type_archive_title(); ?></h1>
           <div class="program-filters">
               <form id="program-filter-form" class="search-form">
                   <div class="form-group">
                       <label for="location-filter">Location</label>
                       <select id="location-filter" class="form-control">
                           <option value="">All Suburbs</option>
                           <?php
                           $locations = get_terms(array(
                               'taxonomy' => 'location',
                               'hide_empty' => true
                           ));
                           
                           foreach($locations as $location) {
                               echo '<option value="' . $location->slug . '">' . $location->slug . '"';
                               if (isset($_GET['location']) && $_GET['location'] === $location->slug) {
                                   echo ' selected';
                               }
                               echo '>' . $location->name . '</option>';
                           }
                           ?>
                       </select>
                   </div>
                   <div class="form-group">
                       <label for="category-filter">Category</label>
                       <select id="category-filter" class="form-control">
                           <option value="">All Categories</option>
                           <?php
                           $categories = get_terms(array(
                               'taxonomy' => 'program_category',
                               'hide_empty' => true
                           ));
                           
                           foreach($categories as $category) {
                               echo '<option value="' . $category->slug . '"';
                               if (isset($_GET['category']) && $_GET['category'] === $category->slug) {
                                   echo ' selected';
                               }
                               echo '>' . $category->name . '</option>';
                           }
                           ?>
                       </select>
                   </div>
                   <div class="form-group">
                       <label for="age-filter">Age Group</label>
                       <select id="age-filter" class="form-control">
                           <option value="">All Ages</option>
                           <?php
                           $age_groups = get_terms(array(
                               'taxonomy' => 'age_group',
                               'hide_empty' => true
                           ));
                           
                           foreach($age_groups as $age_group) {
                               echo '<option value="' . $age_group->slug . '"';
                               if (isset($_GET['age_group']) && $_GET['age_group'] === $age_group->slug) {
                                   echo ' selected';
                               }
                               echo '>' . $age_group->name . '</option>';
                           }
                           ?>
                       </select>
                   </div>
                   <button type="submit" class="btn search-btn">Filter</button>
               </form>
           </div>
       </div>

       <div class="programs-grid">
           <?php
           if (have_posts()) :
               while (have_posts()) : the_post();
                   // Get program data
                   $provider_name = get_post_meta(get_the_ID(), 'provider_name', true);
                   $address = get_post_meta(get_the_ID(), 'address', true);
                   $cost = get_post_meta(get_the_ID(), 'cost', true);
                   
                   // Get terms
                   $categories = wp_get_post_terms(get_the_ID(), 'program_category');
                   $category = !empty($categories) ? $categories[0]->name : '';
                   
                   $age_groups = wp_get_post_terms(get_the_ID(), 'age_group');
                   $age_group = !empty($age_groups) ? $age_groups[0]->name : '';
           ?>
                   <div class="program-card">
                       <div class="program-img">
                           <?php if (has_post_thumbnail()) : ?>
                               <?php the_post_thumbnail('medium'); ?>
                           <?php else : ?>
                               <img src="<?php echo get_template_directory_uri(); ?>/img/placeholder.jpg" alt="<?php the_title(); ?>">
                           <?php endif; ?>
                       </div>
                       <div class="program-details">
                           <?php if ($category) : ?>
                               <span class="program-category"><?php echo $category; ?></span>
                           <?php endif; ?>
                           <h3 class="program-title"><?php the_title(); ?></h3>
                           <div class="program-info">
                               <?php if ($address) : ?>
                                   <p><?php echo $address; ?></p>
                               <?php endif; ?>
                               <?php if ($age_group) : ?>
                                   <p>Ages: <?php echo $age_group; ?></p>
                               <?php endif; ?>
                           </div>
                           <div class="program-rating">
                               <?php
                               // Get average rating
                               $args = array(
                                   'post_id' => get_the_ID(),
                                   'status' => 'approve'
                               );
                               $comments = get_comments($args);
                               $rating_sum = 0;
                               $rating_count = 0;
                               
                               foreach ($comments as $comment) {
                                   $rating = get_comment_meta($comment->comment_ID, 'rating', true);
                                   if ($rating) {
                                       $rating_sum += $rating;
                                       $rating_count++;
                                   }
                               }
                               
                               $average_rating = $rating_count > 0 ? round($rating_sum / $rating_count, 1) : 0;
                               $stars = '';
                               
                               for ($i = 1; $i <= 5; $i++) {
                                   if ($i <= $average_rating) {
                                       $stars .= '★';
                                   } else {
                                       $stars .= '☆';
                                   }
                               }
                               ?>
                               <div class="stars"><?php echo $stars; ?></div>
                               <span class="review-count">(<?php echo $rating_count; ?> reviews)</span>
                           </div>
                           <div class="program-footer">
                               <?php if ($cost) : ?>
                                   <span class="program-price">$<?php echo number_format($cost, 2); ?>/session</span>
                               <?php else : ?>
                                   <span class="program-price">Contact for pricing</span>
                               <?php endif; ?>
                               <a href="<?php the_permalink(); ?>" class="btn btn-secondary">View Details</a>
                           </div>
                       </div>
                   </div>
           <?php
               endwhile;
               
               // Pagination
               the_posts_pagination(array(
                   'mid_size' => 2,
                   'prev_text' => '&laquo; Previous',
                   'next_text' => 'Next &raquo;'
               ));
               
           else :
               echo '<p>No programs found. Please try a different search.</p>';
           endif;
           ?>
       </div>
   </div>

   <?php get_footer(); ?>