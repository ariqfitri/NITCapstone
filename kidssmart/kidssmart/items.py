# Define here the models for your scraped items
#
# See documentation in:
# https://docs.scrapy.org/en/latest/topics/items.html

import scrapy


class KidssmartItem(scrapy.Item):
    # Existing fields
    title = scrapy.Field()
    address = scrapy.Field()
    suburb = scrapy.Field()
    postcode = scrapy.Field()
    activity_type = scrapy.Field()
    
    # NEW fields for unified schema
    source_url = scrapy.Field()
    image = scrapy.Field()
    description = scrapy.Field()
    phone = scrapy.Field()
    email = scrapy.Field()
    website = scrapy.Field()
    age_range = scrapy.Field()
    cost = scrapy.Field()
    schedule = scrapy.Field()# --- ToyItem (added for Toys Spider) ---
import scrapy

class ToyItem(scrapy.Item):
    name = scrapy.Field()
    price = scrapy.Field()
    category = scrapy.Field()
    rating = scrapy.Field()
    availability = scrapy.Field()
    product_url = scrapy.Field()
    sku = scrapy.Field()
    timestamp = scrapy.Field()
