import re

import scrapy
from ..items import KidssmartItem
import scrapy
from ..items import KidssmartItem

class ActivitySpider(scrapy.Spider):
    name = "activities"
    start_urls = [
        'https://www.activeactivities.com.au/directory/category/arts-and-crafts/',
        'https://www.activeactivities.com.au/directory/category/health-and-fitness/',
        'https://www.activeactivities.com.au/directory/category/hobbies/',
    ]

    def parse(self, response):
        match = re.search(r'/category/([^/]+)/', response.url)
        activity_type = match.group(1).replace('-', ' ').title() if match else "Unknown"

        all_div_listing_container = response.css("div.listing-container")

        for activities in all_div_listing_container:
            item = KidssmartItem()

            title = activities.css(".listing-title a::text").get()
            address = activities.css(".listing-address-1::text").get()
            suburb = activities.css(".listing-address-location-bottom::text").get()
            postcode = activities.css(".listing-address-listing-postcode::text").get()



            item["title"] = title.strip() if title else None
            item["address"] = address.strip() if address else None
            item["suburb"] = suburb.strip() if suburb else None
            item["postcode"] = postcode.strip() if postcode else None
            item["activity_type"] = activity_type.strip() if activity_type else None

            yield item
