<?php 
get_header(); 
require_once 'config/db_config.php';
?>

<div class="container">
    <div class="program-single">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                
                // Get program data
                $provider_name = get_post_meta(get_the_ID(), 'provider_name', true);
                $provider_phone = get_post_meta(get_the_ID(), 'provider_phone', true);
                $provider_email = get_post_meta(get_the_ID(), 'provider_email', true);
                $provider_website = get_post_meta(get_the_ID(), 'provider_website', true);
                $address = get_post_meta(get_the_ID(), 'address', true);
                $cost = get_post_meta(get_the_ID(), 'cost', true);
                
                // Get terms
                $categories = wp_get_post_terms(get_the_ID(), 'program_category');
                $category = !empty($categories) ? $categories[0]->name : '';
                
                $age_groups = wp_get_post_terms(get_the_ID(), 'age_group');
                $age_group = !empty($age_groups) ? $age_groups[0]->name : '';
                
                $locations = wp_get_post_terms(get_the_ID(), 'location');
                $location = !empty($locations) ? $locations[0]->name : '';
        ?>
                <div class="program-header">
                    <div class="program-header-left">
                        <h1 class="program-title"><?php the_title(); ?></h1>
                        <?php if ($category) : ?>
                            <span class="program-category"><?php echo $category; ?></span>
                        <?php endif; ?>
                        
                        <div class="program-meta">
                            <?php if ($address) : ?>
                                <div class="meta-item">
                                    <span class="meta-label">Location:</span>
                                    <span class="meta-value"><?php echo $address; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($age_group) : ?>
                                <div class="meta-item">
                                    <span class="meta-label">Age Group:</span>
                                    <span class="meta-value"><?php echo $age_group; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($cost) : ?>
                                <div class="meta-item">
                                    <span class="meta-label">Cost:</span>
                                    <span class="meta-value">$<?php echo number_format($cost, 2); ?>/session</span>
                                </div>
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
                            <span class="review-count"><?php echo $average_rating; ?> (<?php echo $rating_count; ?> reviews)</span>
                        </div>
                    </div>
                    
                    <div class="program-header-right">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="program-featured-image">
                                <?php the_post_thumbnail('large'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="program-content">
                    <div class="program-description">
                        <h2>Program Description</h2>
                        <?php the_content(); ?>
                    </div>
                    
                    <div class="program-provider">
                        <h2>Provider Information</h2>
                        <div class="provider-details">
                            <?php if ($provider_name) : ?>
                                <div class="provider-item">
                                    <span class="provider-label">Provider Name:</span>
                                    <span class="provider-value"><?php echo $provider_name; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($provider_phone) : ?>
                                <div class="provider-item">
                                    <span class="provider-label">Phone:</span>
                                    <span class="provider-value"><?php echo $provider_phone; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($provider_email) : ?>
                                <div class="provider-item">
                                    <span class="provider-label">Email:</span>
                                    <span class="provider-value"><?php echo $provider_email; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($provider_website) : ?>
                                <div class="provider-item">
                                    <span class="provider-label">Website:</span>
                                    <span class="provider-value"><a href="<?php echo esc_url($provider_website); ?>" target="_blank"><?php echo $provider_website; ?></a></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="program-reviews">
                    <h2>Reviews</h2>
                    
                    <?php if (is_user_logged_in()) : ?>
                        <div class="review-form">
                            <h3>Write a Review</h3>
                            <form id="program-review-form" method="post">
                                <div class="rating-select">
                                    <label>Your Rating:</label>
                                    <div class="rating-stars">
                                        <input type="radio" name="rating" value="5" id="rating-5" required><label for="rating-5">★</label>
                                        <input type="radio" name="rating" value="4" id="rating-4"><label for="rating-4">★</label>
                                        <input type="radio" name="rating" value="3" id="rating-3"><label for="rating-3">★</label>
                                        <input type="radio" name="rating" value="2" id="rating-2"><label for="rating-2">★</label>
                                        <input type="radio" name="rating" value="1" id="rating-1"><label for="rating-1">★</label>
                                    </div>
                                </div>
                                
                                <div class="review-content">
                                    <label for="review-text">Your Review:</label>
                                    <textarea id="review-text" name="comment" rows="5" maxlength="500" required></textarea>
                                    <div class="word-count"><span id="word-count">0</span>/500 characters</div>
                                </div>
                                
                                <input type="hidden" name="comment_post_ID" value="<?php the_ID(); ?>">
                                <input type="hidden" name="action" value="submit_review">
                                <?php wp_nonce_field('submit_review_nonce', 'review_nonce'); ?>
                                
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    <?php else : ?>
                        <div class="review-login-prompt">
                            <p>Please <a href="#" id="loginReviewBtn">login</a> to submit a review.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="review-list">
                        <?php
                        // List reviews
                        $args = array(
                            'post_id' => get_the_ID(),
                            'status' => 'approve'
                        );
                        $comments = get_comments($args);
                        
                        if (!empty($comments)) :
                            foreach ($comments as $comment) :
                                $rating = get_comment_meta($comment->comment_ID, 'rating', true);
                                $commenter = get_userdata($comment->user_id);
                                $stars = '';
                                
                                if ($rating) {
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            $stars .= '★';
                                        } else {
                                            $stars .= '☆';
                                        }
                                    }
                                }
                        ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <span class="reviewer-name"><?php echo $commenter->display_name; ?></span>
                                            <span class="review-date"><?php echo get_comment_date('F j, Y', $comment->comment_ID); ?></span>
                                        </div>
                                        <div class="review-rating">
                                            <span class="stars"><?php echo $stars; ?></span>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <?php echo wpautop($comment->comment_content); ?>
                                    </div>
                                </div>
                        <?php
                            endforeach;
                        else :
                            echo '<p>No reviews yet. Be the first to review this program!</p>';
                        endif;
                        ?>
                    </div>
                </div>
        <?php
            endwhile;
        else :
            echo '<p>Program not found.</p>';
        endif;
        ?>
    </div>
</div>

<?php get_footer(); ?>