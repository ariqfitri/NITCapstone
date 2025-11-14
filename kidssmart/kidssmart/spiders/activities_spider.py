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
        
        self.logger.info(f"Processing {len(all_div_listing_container)} activities on page: {response.url}")
        
        for activities in all_div_listing_container:
            item = KidssmartItem()
            
            # Basic fields
            title = activities.css(".listing-title a::text").get()
            address = activities.css(".listing-address-1::text").get()
            suburb = activities.css(".listing-address-location-bottom::text").get()
            postcode = activities.css(".listing-address-listing-postcode::text").get()
            
            # Extract URL for source_url (CRITICAL FIX)
            listing_url = activities.css(".listing-title a::attr(href)").get()
            
            # Extract additional fields
            image = activities.css(".listing-item-img img::attr(src)").get()
            description = activities.css(".listing-item-content p::text").get()
            
            # CRITICAL FIXES:
            # 1. Always provide a source_url - use current page URL if listing URL fails
            if listing_url:
                source_url = response.urljoin(listing_url)
            else:
                # Fallback: create unique URL using title + current page
                safe_title = re.sub(r'[^a-zA-Z0-9\-]', '', title.replace(' ', '-').lower()) if title else 'unknown'
                source_url = f"{response.url}#{safe_title}"
            
            # 2. Use 'category' field instead of 'activity_type' (pipeline expects 'category')
            # 3. Validate all required fields exist
            
            if not title or not title.strip():
                self.logger.warning(f"Skipping activity with empty title on {response.url}")
                continue
                
            # Populate item with proper field mapping
            item["title"] = title.strip()
            item["address"] = address.strip() if address else None
            item["suburb"] = suburb.strip() if suburb else None
            item["postcode"] = postcode.strip() if postcode else None
            item["category"] = activity_type.strip()  # FIXED: Use 'category' not 'activity_type'
            
            # Required field with proper fallback
            item["source_url"] = source_url  # FIXED: Always has a value, never None
            
            # Optional fields
            item["image_url"] = response.urljoin(image) if image else None
            item["description"] = description.strip() if description else None
            item["phone"] = None
            item["email"] = None
            item["website"] = None
            item["age_range"] = None
            item["cost"] = None
            item["schedule"] = None
            
            self.logger.info(f"Yielding activity: {title} with URL: {source_url}")
            yield item
        
        # Follow pagination if available
        next_page = response.css("a.next::attr(href)").get()
        if next_page:
            self.logger.info(f"Following next page: {next_page}")
            yield response.follow(next_page, callback=self.parse)
        else:
            self.logger.info("No more pages found")