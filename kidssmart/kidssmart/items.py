# Define here the models for your scraped items
#
# See documentation in:
# https://docs.scrapy.org/en/latest/topics/items.html

import scrapy


class KidssmartItem(scrapy.Item):
    # define the fields for your item here like:
    title = scrapy.Field()
    address = scrapy.Field()
    suburb = scrapy.Field()
    postcode = scrapy.Field()
    activity_type = scrapy.Field()
    description = scrapy.Field()
    image = scrapy.Field()

