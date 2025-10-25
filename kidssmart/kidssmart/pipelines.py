# Define your item pipelines here
#
# Don't forget to add your pipeline to the ITEM_PIPELINES setting
# See: https://docs.scrapy.org/en/latest/topics/item-pipeline.html

from itemadapter import ItemAdapter
import pymysql
from datetime import datetime
import os

class MySQLActivityPipeline:
    """
    Unified pipeline for all activity scrapers.
    Works with Docker MySQL and saves to kidssmart_app database.
    """
    
    def __init__(self):
        self.connection = None
        self.cursor = None
    
    def open_spider(self, spider):
        """Connect to Docker MySQL database when spider opens"""
        try:
            self.connection = pymysql.connect(
                host=os.getenv('DB_HOST', 'database'),  # Docker service name
                user=os.getenv('DB_SCRAPER_USER', 'scraper_user'),
                password=os.getenv('DB_SCRAPER_PASSWORD', 'ScraperPass123!'),
                database='kidssmart_app',
                charset='utf8mb4',
                cursorclass=pymysql.cursors.DictCursor
            )
            self.cursor = self.connection.cursor()
            spider.logger.info("✅ Database connection opened successfully")
        except Exception as e:
            spider.logger.error(f"❌ Database connection failed: {e}")
            raise
    
    def close_spider(self, spider):
        """Close database connection when spider closes"""
        if self.cursor:
            self.cursor.close()
        if self.connection:
            self.connection.close()
        spider.logger.info("Database connection closed")
    
    def process_item(self, item, spider):
        """
        Process and save activity to database.
        Detects duplicates by source_url or title.
        """
        
        # Map old field names to new schema
        title = item.get('title')
        address = item.get('address')
        suburb = item.get('suburb')
        postcode = item.get('postcode')
        category = item.get('activity_type') or item.get('category')
        image_url = item.get('image') or item.get('image_url')
        description = item.get('description')
        source_url = item.get('source_url') or item.get('url')
        
        # Additional fields (if your spiders provide them)
        phone = item.get('phone')
        email = item.get('email')
        website = item.get('website')
        age_range = item.get('age_range')
        cost = item.get('cost')
        schedule = item.get('schedule')
        
        # Use spider name as source
        source_name = spider.name
        
        # Check for duplicates by source_url or title
        try:
            if source_url:
                self.cursor.execute(
                    "SELECT activity_id FROM activities WHERE source_url = %s",
                    (source_url,)
                )
            else:
                # Fallback to title if no source_url
                self.cursor.execute(
                    "SELECT activity_id FROM activities WHERE title = %s AND suburb = %s",
                    (title, suburb)
                )
            
            existing = self.cursor.fetchone()
            
            if existing:
                spider.logger.info(f"⚠️  Duplicate found: {title} - Skipping")
                return item
            
            # Insert new activity
            self.cursor.execute(
                """INSERT INTO activities 
                (title, description, category, suburb, postcode, address, phone, email, 
                 website, age_range, cost, schedule, image_url, source_url, source_name, scraped_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                (
                    title,
                    description,
                    category,
                    suburb,
                    postcode,
                    address,
                    phone,
                    email,
                    website,
                    age_range,
                    cost,
                    schedule,
                    image_url,
                    source_url,
                    source_name,
                    datetime.now()
                )
            )
            
            self.connection.commit()
            spider.logger.info(f"✅ Saved activity: {title} from {source_name}")
        
        except Exception as e:
            self.connection.rollback()
            spider.logger.error(f"❌ Error saving activity '{title}': {e}")
        
        return item


# Legacy pipeline (keep for backward compatibility if needed)
class KidssmartPipeline:
    """
    DEPRECATED: Use MySQLActivityPipeline instead.
    This is kept for backward compatibility only.
    """
    
    def __init__(self):
        spider.logger.warning("⚠️  KidssmartPipeline is deprecated. Use MySQLActivityPipeline instead.")
