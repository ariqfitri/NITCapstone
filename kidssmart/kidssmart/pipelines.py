# --- MySQLActivityPipeline (for activities and kidsbook) ---
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
            # Don't raise exception; just log and skip DB saving
            self.connection = None
            self.cursor = None

    def close_spider(self, spider):
        """Close database connection"""
        if self.cursor:
            self.cursor.close()
        if self.connection:
            self.connection.close()
        spider.logger.info("Database connection closed")

    def process_item(self, item, spider):
        """Process item and save to database if DB is available"""
        if self.connection and self.cursor:
            try:
                self._save_to_db(item, spider)
            except Exception as e:
                spider.logger.error(f"Error saving to DB: {e}")
        return item

    def _save_to_db(self, item, spider):
        """Save item to DB (simplified for demo)"""
        # Example: Only insert title if present
        if not item.get('title'):
            return
        try:

            #self.cursor.execute(
                #"INSERT INTO activities (title, scraped_at) VALUES (%s, %s)",
                #(item.get('title'), datetime.now())
            #)
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
            spider.logger.info(f"Saved activity: {item.get('title')}")
        except Exception as e:
            self.connection.rollback()
            spider.logger.error(f"Error saving activity '{item.get('title')}': {e}")


# --- ToysPipeline (for Toys Spider) ---
import json
import csv
from pathlib import Path

class ToysPipeline:
    def open_spider(self, spider):
        out_dir = Path('outputs')

