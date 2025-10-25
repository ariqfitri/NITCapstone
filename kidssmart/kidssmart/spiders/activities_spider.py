import re
import scrapy
from ..items import KidssmartItem


class ActivitySpider(scrapy.Spider):
    name = "activities"
    
    start_urls = [
        'https://www.activeactivities.com.au/directory/category/arts-and-crafts/',
        'https://www.activeactivities.com.au/directory/category/health-and-fitness/',
        'https://www.activeactivities.com.au/directory/category/hobbies/',
        'https://www.activeactivities.com.au/directory/category/community/',
        'https://www.activeactivities.com.au/directory/category/education/',
        'https://www.activeactivities.com.au/directory/category/entertainment/',
        'https://www.activeactivities.com.au/directory/category/holidays/',
        'https://www.activeactivities.com.au/directory/category/outdoor-and-adventure/',
        'https://www.activeactivities.com.au/directory/category/parties/',
        'https://www.activeactivities.com.au/directory/category/performing-arts/',
        'https://www.activeactivities.com.au/directory/category/play/',
        'https://www.activeactivities.com.au/directory/category/sports/'
    ]

    def parse(self, response):
        # Extract category from URL
        match = re.search(r'/category/([^/]+)/', response.url)
        activity_type = match.group(1).replace('-', ' ').title() if match else "Unknown"
        
        # Extract all activities from page
        all_div_listing_container = response.css("div.listing-container")
        
        for activities in all_div_listing_container:
            item = KidssmartItem()
            
            # Basic fields (already working)
            title = activities.css(".listing-title a::text").get()
            address = activities.css(".listing-address-1::text").get()
            suburb = activities.css(".listing-address-location-bottom::text").get()
            postcode = activities.css(".listing-address-listing-postcode::text").get()
            
            # NEW: Extract URL for source_url (prevents duplicates)
            listing_url = activities.css(".listing-title a::attr(href)").get()
            
            # NEW: Extract image if available
            image = activities.css(".listing-item-img img::attr(src)").get()
            
            # NEW: Extract description if available
            description = activities.css(".listing-item-content p::text").get()
            
            # Populate item
            item["title"] = title.strip() if title else None
            item["address"] = address.strip() if address else None
            item["suburb"] = suburb.strip() if suburb else None
            item["postcode"] = postcode.strip() if postcode else None
            item["activity_type"] = activity_type.strip() if activity_type else None
            
            # NEW fields for pipeline
            item["source_url"] = response.urljoin(listing_url) if listing_url else None
            item["image"] = response.urljoin(image) if image else None
            item["description"] = description.strip() if description else None

            required_fields = [
                item["title"],
                item["address"],
                item["suburb"],
                item["postcode"],
                item["activity_type"],
                item["image"],
                item["description"]
            ]

            if all(required_fields):  # only yield if ALL fields are present
                yield item
            else:
                self.logger.info(f" Skipped incomplete record: {item['title']}")
        
        # NEW: Follow pagination if available
        next_page = response.css("a.next::attr(href)").get()
        if next_page:
            yield response.follow(next_page, callback=self.parse)
