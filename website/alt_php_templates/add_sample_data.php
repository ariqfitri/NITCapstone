<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Sample activities data
$sample_activities = [
    [
        'title' => 'Kids Soccer Training',
        'description' => 'Fun soccer training for children aged 5-12. Learn basic skills, teamwork, and have fun!',
        'category' => 'Sports',
        'suburb' => 'Melbourne',
        'postcode' => '3000',
        'address' => '123 Sports Street, Melbourne',
        'phone' => '(03) 1234 5678',
        'email' => 'soccer@example.com',
        'website' => 'https://soccer-training.example.com',
        'age_range' => '5-12 years',
        'cost' => '$20 per session',
        'schedule' => 'Monday & Wednesday: 4-5 PM',
        'image_url' => 'https://via.placeholder.com/400x200?text=Soccer+Training',
        'source_name' => 'sample',
        'is_approved' => 1
    ],
    [
        'title' => 'Art & Craft Classes',
        'description' => 'Creative art and craft classes where children can explore painting, drawing, and crafting.',
        'category' => 'Arts & Crafts',
        'suburb' => 'Sydney',
        'postcode' => '2000',
        'address' => '456 Art Lane, Sydney',
        'phone' => '(02) 9876 5432',
        'email' => 'art@example.com',
        'website' => 'https://art-classes.example.com',
        'age_range' => '4-10 years',
        'cost' => '$25 per class',
        'schedule' => 'Tuesday & Thursday: 3:30-4:30 PM',
        'image_url' => 'https://via.placeholder.com/400x200?text=Art+Classes',
        'source_name' => 'sample',
        'is_approved' => 1
    ],
    [
        'title' => 'Swimming Lessons',
        'description' => 'Professional swimming lessons for all skill levels. Certified instructors and safe environment.',
        'category' => 'Swimming',
        'suburb' => 'Brisbane',
        'postcode' => '4000',
        'address' => '789 Pool Road, Brisbane',
        'phone' => '(07) 5555 1234',
        'email' => 'swim@example.com',
        'website' => 'https://swim-lessons.example.com',
        'age_range' => '3-15 years',
        'cost' => '$30 per lesson',
        'schedule' => 'Monday to Friday: 9 AM - 6 PM',
        'image_url' => 'https://via.placeholder.com/400x200?text=Swimming+Lessons',
        'source_name' => 'sample',
        'is_approved' => 1
    ],
    [
        'title' => 'Music Academy',
        'description' => 'Learn piano, guitar, violin, and voice lessons with experienced music teachers.',
        'category' => 'Music',
        'suburb' => 'Perth',
        'postcode' => '6000',
        'address' => '321 Music Avenue, Perth',
        'phone' => '(08) 4444 8888',
        'email' => 'music@example.com',
        'website' => 'https://music-academy.example.com',
        'age_range' => '6-18 years',
        'cost' => '$35 per 30-minute lesson',
        'schedule' => 'Flexible hours by appointment',
        'image_url' => 'https://via.placeholder.com/400x200?text=Music+Academy',
        'source_name' => 'sample',
        'is_approved' => 1
    ],
    [
        'title' => 'Dance Studio',
        'description' => 'Ballet, jazz, hip-hop, and contemporary dance classes for children of all ages.',
        'category' => 'Dance',
        'suburb' => 'Adelaide',
        'postcode' => '5000',
        'address' => '654 Dance Street, Adelaide',
        'phone' => '(08) 7777 9999',
        'email' => 'dance@example.com',
        'website' => 'https://dance-studio.example.com',
        'age_range' => '3-16 years',
        'cost' => '$28 per class',
        'schedule' => 'Various classes throughout the week',
        'image_url' => 'https://via.placeholder.com/400x200?text=Dance+Studio',
        'source_name' => 'sample',
        'is_approved' => 1
    ],
    [
        'title' => 'Science Club',
        'description' => 'Hands-on science experiments and learning activities for curious young minds.',
        'category' => 'Education',
        'suburb' => 'Melbourne',
        'postcode' => '3000',
        'address' => '987 Science Road, Melbourne',
        'phone' => '(03) 3333 4444',
        'email' => 'science@example.com',
        'website' => 'https://science-club.example.com',
        'age_range' => '7-14 years',
        'cost' => '$40 per month',
        'schedule' => 'Saturday: 10 AM - 12 PM',
        'image_url' => 'https://via.placeholder.com/400x200?text=Science+Club',
        'source_name' => 'sample',
        'is_approved' => 1
    ]
];

try {
    $inserted = 0;
    foreach ($sample_activities as $activity) {
        $query = "INSERT INTO activities (title, description, category, suburb, postcode, address, phone, email, website, age_range, cost, schedule, image_url, source_name, is_approved) 
                  VALUES (:title, :description, :category, :suburb, :postcode, :address, :phone, :email, :website, :age_range, :cost, :schedule, :image_url, :source_name, :is_approved)";
        
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($activity)) {
            $inserted++;
        }
    }
    
    echo "Successfully inserted $inserted sample activities!\n";
    
    // Show what was inserted
    $stmt = $db->query("SELECT activity_id, title, category, suburb FROM activities WHERE source_name = 'sample'");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nInserted activities:\n";
    foreach ($results as $row) {
        echo "- {$row['title']} ({$row['category']}) in {$row['suburb']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>