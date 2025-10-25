# Define your item pipelines here
#
# Don't forget to add your pipeline to the ITEM_PIPELINES setting
# See: https://docs.scrapy.org/en/latest/topics/item-pipeline.html


# useful for handling different item types with a single interface
from itemadapter import ItemAdapter

import mysql.connector

class KidssmartPipeline:

    def __init__(self):
        self.create_connection()
        self.create_table()

    def create_connection(self):
        self.conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",         # XAMPP default has no password
            database="kidssmart" # make sure this database exists in phpMyAdmin
        )
        self.curr = self.conn.cursor()  # <-- fixed typo: was self.com.cursor()

    def create_table(self):
        self.curr.execute("""
            CREATE TABLE IF NOT EXISTS activities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) UNIQUE,
                address VARCHAR(255),
                suburb VARCHAR(255),
                postcode VARCHAR(20),
                activity_type VARCHAR(100)
            )
        """)

    def store_db(self, item):
        self.curr.execute("""
            INSERT INTO activities (title, address, suburb, postcode, activity_type)
            VALUES (%s, %s, %s, %s, %s)
        """, (
            item.get('title'),
            item.get('address'),
            item.get('suburb'),
            item.get('postcode'),
            item.get('activity_type')
        ))
        self.conn.commit()

    def process_item(self, item, spider):
        print(f" Pipeline received: {item['title']}")
        self.store_db(item)
        return item
