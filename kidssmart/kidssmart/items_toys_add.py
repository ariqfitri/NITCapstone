# --- ToyItem (added for toys spider) ---
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
