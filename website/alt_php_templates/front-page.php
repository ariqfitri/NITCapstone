<?php 
    get_header(); 
    require_once 'config/db_config.php';
?>

   <!-- Hero Section -->
   <section class="hero">
       <div class="container">
           <h2>Find the Perfect After-School Programs for Your Child</h2>
           <p>Discover and compare hundreds of quality programs in Melbourne and surrounding areas</p>
           
           <div class="search-container">
               <form class="search-form" id="program-search-form">
                   <div class="form-group">
                       <label for="location">Location</label>
                       <select id="location" class="form-control">
                           <option value="">All Suburbs</option>
                           <?php
                           $locations = get_terms(array(
                               'taxonomy' => 'location',
                               'hide_empty' => true
                           ));
                           
                           foreach($locations as $location) {
                               echo '<option value="' . $location->slug . '">' . $location->name . '</option>';
                           }
                           ?>
                       </select>
                   </div>
                   <div class="form-group">
                       <label for="category">Category</label>
                       <select id="category" class="form-control">
                           <option value="">All Categories</option>
                           <?php
                           $categories = get_terms(array(
                               'taxonomy' => 'program_category',
                               'hide_empty' => true
                           ));
                           
                           foreach($categories as $category) {
                               echo '<option value="' . $category->slug . '">' . $category->name . '</option>';
                           }
                           ?>
                       </select>
                   </div>
                   <div class="form-group">
                       <label for="ageGroup">Age Group</label>
                       <select id="ageGroup" class="form-control">
                           <option value="">All Ages</option>
                           <?php
                           $age_groups = get_terms(array(
                               'taxonomy' => 'age_group',
                               'hide_empty' => true
                           ));
                           
                           foreach($age_groups as $age_group) {
                               echo '<option value="' . $age_group->slug . '">' . $age_group->name . '</option>';
                           }
                           ?>
                       </select>
                   </div>
                   <button type="submit" class="btn search-btn">Search Programs</button>
               </form>
           </div>
       </div>
   </section>

   <!-- Featured Programs Section -->
   <section class="section">
       <div class="container">
           <div class="section-header">
               <h2>Featured Programs</h2>
               <p>Discover top-rated after-school activities for your children</p>
           </div>
           
           <div class="programs-grid">
               <?php
               $args = array(
                   'post_type' => 'program',
                   'posts_per_page' => 6,
                   'meta_key' => '_featured',
                   'meta_value' => 'yes'
               );
               
               $featured_query = new WP_Query($args);
               
               if($featured_query->have_posts()) {
                   while($featured_query->have_posts()) {
                       $featured_query->the_post();
                       
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
                               <?php if(has_post_thumbnail()): ?>
                                   <?php the_post_thumbnail('medium'); ?>
                               <?php else: ?>
                                   <img src="<?php echo get_template_directory_uri(); ?>/img/placeholder.jpg" alt="<?php the_title(); ?>">
                               <?php endif; ?>
                           </div>
                           <div class="program-details">
                               <?php if($category): ?>
                                   <span class="program-category"><?php echo $category; ?></span>
                               <?php endif; ?>
                               <h3 class="program-title"><?php the_title(); ?></h3>
                               <div class="program-info">
                                   <?php if($address): ?>
                                       <p><?php echo $address; ?></p>
                                   <?php endif; ?>
                                   <?php if($age_group): ?>
                                       <p>Ages: <?php echo $age_group; ?></p>
                                   <?php endif; ?>
                               </div>
                               <div class="program-rating">
                                   <div class="stars">★★★★★</div>
                                   <span class="review-count">(18 reviews)</span>
                               </div>
                               <div class="program-footer">
                                   <?php if($cost): ?>
                                       <span class="program-price">$<?php echo number_format($cost, 2); ?>/session</span>
                                   <?php else: ?>
                                       <span class="program-price">Contact for pricing</span>
                                   <?php endif; ?>
                                   <a href="<?php the_permalink(); ?>" class="btn btn-secondary">View Details</a>
                               </div>
                           </div>
                       </div>
                       <?php
                   }
                   wp_reset_postdata();
               } else {
                   echo '<p>No featured programs found.</p>';
               }
               ?>
           </div>
           
           <div style="text-align: center; margin-top: 30px;">
               <a href="<?php echo get_post_type_archive_link('program'); ?>" class="btn btn-primary">Browse All Programs</a>
           </div>
       </div>
   </section>

   <!-- Features Section -->
   <section class="section" style="background-color: #f1f5fe;">
       <div class="container">
           <div class="section-header">
               <h2>Why Choose KidsSmart</h2>
               <p>Our platform makes finding after-school programs simple and stress-free</p>
           </div>
           
           <div class="features-grid">
               <!-- Feature 1 -->
               <div class="feature-card">
                   <div class="feature-icon">🔍</div>
                   <h3 class="feature-title">Easy Search</h3>
                   <p>Find programs by location, category, age group, and more with our advanced filters</p>
               </div>
               
               <!-- Feature 2 -->
               <div class="feature-card">
                   <div class="feature-icon">⭐</div>
                   <h3 class="feature-title">Trusted Reviews</h3>
                   <p>Read authentic reviews from parents who have experienced the programs firsthand</p>
               </div>
               
               <!-- Feature 3 -->
               <div class="feature-card">
                   <div class="feature-icon">📱</div>
                   <h3 class="feature-title">Stay Updated</h3>
                   <p>Receive notifications about new programs and special offers in your area</p>
               </div>
           </div>
       </div>
   </section>

   <?php get_footer(); ?>