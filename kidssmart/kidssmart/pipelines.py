import pymysql
from datetime import datetime
import os

class MySQLActivityPipeline:
    def __init__(self):
        self.connection = None
        self.cursor = None

    def open_spider(self, spider):
        """Connect to database when spider starts"""
        try:
            self.connection = pymysql.connect(
                host=os.getenv('DB_HOST', 'database'),
                user=os.getenv('SCRAPER_USER', 'scraper_user'),
                password=os.getenv('SCRAPER_PASSWORD', 'ScraperPass123!'),
                database='kidssmart_app',
                charset='utf8mb4',
                cursorclass=pymysql.cursors.DictCursor
            )
            self.cursor = self.connection.cursor()
            spider.logger.info("Database connection opened")
        except Exception as e:
            spider.logger.error(f"Database connection failed: {e}")
            raise

    def close_spider(self, spider):
        """Close database connection"""
        if self.cursor:
            self.cursor.close()
        if self.connection:
            self.connection.close()
        spider.logger.info("Database connection closed")

    def process_item(self, item, spider):
        """Process item and save to database"""
        
        # Normalize kidsbook format to database format
        if 'provider_name' in item:
            # This is from kidsbook spider - normalize it
            normalized_items = self._normalize_kidsbook_item(item, spider)
            for normalized in normalized_items:
                self._save_to_db(normalized, spider)
        else:
            # This is from activities spider - already has 'title'
            self._save_to_db(item, spider)
        
        return item

    def _normalize_kidsbook_item(self, item, spider):
        """Convert kidsbook structure to database structure"""
        normalized_items = []
        
        provider_name = item.get('provider_name', '')
        category = item.get('category', '')
        description = item.get('description', '')
        provider_url = item.get('provider_url', '')
        
        contact = item.get('contact', {})
        phone = contact.get('phone', '')
        email = contact.get('email', '')
        website = contact.get('website', '')
        
        addresses = item.get('addresses', [])
        
        if addresses:
            for addr in addresses:
                normalized_items.append({
                    'title': provider_name,
                    'description': description,
                    'category': category,
                    'suburb': addr.get('suburb', ''),
                    'postcode': addr.get('postcode', ''),
                    'address': addr.get('street_address', ''),
                    'phone': phone,
                    'email': email,
                    'website': website,
                    'source_url': provider_url,
                })
        else:
            normalized_items.append({
                'title': provider_name,
                'description': description,
                'category': category,
                'suburb': '',
                'postcode': '',
                'address': '',
                'phone': phone,
                'email': email,
                'website': website,
                'source_url': provider_url,
            })
        
        return normalized_items

    def _save_to_db(self, item, spider):
        """Save normalized item to database"""
        # Check for duplicates
        if item.get('source_url'):
            self.cursor.execute(
                "SELECT activity_id FROM activities WHERE source_url = %s",
                (item.get('source_url'),)
            )
        else:
            self.cursor.execute(
                "SELECT activity_id FROM activities WHERE title = %s AND suburb = %s",
                (item.get('title'), item.get('suburb'))
            )
        
        existing = self.cursor.fetchone()
        
        if existing:
            spider.logger.info(f"Duplicate found: {item.get('title')} - Skipping")
            return
        
        # Insert new activity
        try:
            self.cursor.execute("""
                INSERT INTO activities (
                    title, description, category, suburb, postcode, address,
                    phone, email, website, image_url, source_url, source_name, scraped_at
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """, (
                item.get('title'),
                item.get('description'),
                item.get('category'),
                item.get('suburb'),
                item.get('postcode'),
                item.get('address'),
                item.get('phone'),
                item.get('email'),
                item.get('website'),
                item.get('image_url'),
                item.get('source_url'),
                spider.name,
                datetime.now()
            ))
            
            self.connection.commit()
            spider.logger.info(f"Saved activity: {item.get('title')} from {spider.name}")
            
        except Exception as e:
            self.connection.rollback()
            spider.logger.error(f"Error saving activity '{item.get('title')}': {e}")
