import os
import scrapy
import json
import re
from urllib.parse import urlparse
from ..items import KidssmartItem


class SerpapiSpider(scrapy.Spider):
    name = "serpapi"
    allowed_domains = ["serpapi.com"]
    custom_settings = {
        "ROBOTSTXT_OBEY": False,
        "CONCURRENT_REQUESTS": 16,
        "CONCURRENT_REQUESTS_PER_DOMAIN": 16,
        "AUTOTHROTTLE_ENABLED": True,
        "AUTOTHROTTLE_START_DELAY": 0.5,
        "AUTOTHROTTLE_MAX_DELAY": 3,
        "AUTOTHROTTLE_TARGET_CONCURRENCY": 4,
        "DOWNLOAD_DELAY": 1.5,
        "DOWNLOAD_HANDLERS": {
            "http": "scrapy.core.downloader.handlers.http.HTTPDownloadHandler",
            "https": "scrapy.core.downloader.handlers.http.HTTPDownloadHandler",
        },
    }

    # Categories
    CATEGORIES = {
        "Art": ["art", "gallery", "museum", "craft"],
        "Dance": ["dance", "ballet"],
        "Drama": ["drama", "theatre", "acting"],
        "Martial Arts": ["martial arts", "karate", "taekwondo", "judo"],
        "Music": ["music", "singing", "choir", "band"],
        "STEM": ["stem", "robotics", "coding", "science", "technology", "engineering"],
        "Sport": ["sport", "soccer", "basketball", "tennis", "cricket", "swimming"],
        "Tutoring": ["tutoring", "education", "school", "learning", "math", "english"],
        "Wellbeing": ["wellbeing", "mindfulness", "yoga", "fitness", "health"],
    }

    def start_requests(self):
        api_key = os.getenv("SERPAPI_API_KEY")
        if not api_key:
            self.logger.error("SERPAPI_API_KEY not found in environment variables.")
            return

        base_location = "Melbourne"
        for category, keywords in self.CATEGORIES.items():
            for keyword in keywords:
                query = f"{keyword} classes for kids in {base_location}"
                url = (
                    f"https://serpapi.com/search.json?"
                    f"engine=google_maps&q={query}&type=search&photos=true&api_key={api_key}"
                )
                yield scrapy.Request(
                    url, callback=self.parse, meta={"category": category, "query": query}
                )

    def parse(self, response):
        try:
            data = json.loads(response.text)
        except json.JSONDecodeError:
            self.logger.error("Failed to parse JSON from SerpApi response.")
            return

        places = data.get("local_results", [])
        if not places:
            self.logger.warning(f"No results found for query: {response.meta['query']}")
            return

        category = response.meta["category"]

        for place in places:
            item = KidssmartItem()
            item["title"] = place.get("title")
            item["address"] = place.get("address")
            item["suburb"] = self.extract_suburb(place.get("address"))
            item["postcode"] = self.extract_postcode(place.get("address"))
            item["category"] = category

            if "Australia" not in (place.get("address") or ""):
                self.logger.info(f"Skipping non-Australian result: {item['title']} - {item['address']}")
                continue

            item["description"] = (
                place.get("description")
                or place.get("snippet")
                or "Kids-related activity or venue"
            )
            item["phone"] = place.get("phone")
            item["website"] = place.get("website")
            item["email"] = None
            item["age_range"] = None
            item["cost"] = None
            item["schedule"] = None
            item["source_url"] = place.get("links", {}).get("website")

            # --- IMAGE HANDLING SECTION ---
            photo_url = None

            # First: try scraping the place's website for the largest image
            if item.get("website"):
                yield scrapy.Request(
                    item["website"],
                    callback=self.parse_website_image,
                    meta={"item": item},
                    dont_filter=True,
                )
            else:
                # If no website, fallback to SerpAPI images
                photo_url = self.get_serpapi_image(place)
                item["image_url"] = photo_url
                yield item
            # --- END IMAGE HANDLING ---

            if item["website"]:
                yield scrapy.Request(
                    item["website"],
                    callback=self.parse_website,
                    meta={"item": item},
                    dont_filter=True,
                )
            else:
                yield item

    def parse_website(self, response):
        item = response.meta["item"]

        meta_desc = response.xpath("//meta[@name='description']/@content").get()
        if meta_desc:
            item["description"] = meta_desc.strip()

        email = response.xpath(
            "//*[contains(text(),'@') and contains(text(),'.')]/text()"
        ).re_first(r"[\w\.-]+@[\w\.-]+\.\w+")
        if email:
            item["email"] = email

        schedule = response.xpath(
            "//text()[contains(.,'Monday') or contains(.,'Hours') or contains(.,'Open')]"
        ).get()
        if schedule:
            item["schedule"] = schedule.strip()

        body_text = " ".join(response.xpath("//body//text()").getall())
        if "free" in body_text.lower():
            item["cost"] = "Free"
        elif "$" in body_text:
            item["cost"] = "Paid"

        age_match = re.search(
            r"(?:Ages?|Kids?\s*(?:aged)?)[^\d]*(\d{1,2})[^\d]*(\d{1,2})?",
            body_text,
            re.I,
        )
        if age_match:
            if age_match.group(2):
                item["age_range"] = f"{age_match.group(1)}–{age_match.group(2)}"
            else:
                item["age_range"] = f"{age_match.group(1)}+"

        #️Try to extract better image from website if missing or low quality
        if not item.get("image_url"):
            images = response.xpath("//img/@src").getall()
            valid_images = [i for i in images if i.startswith("http") and not i.endswith(".svg")]
            for img in valid_images:
                if not any(x in img.lower() for x in ["logo", "icon", "placeholder"]):
                    item["image_url"] = img
                    break

        yield item

    def extract_suburb(self, address):
        if not address:
            return None
        parts = address.split(",")
        if len(parts) > 1:
            return parts[-2].strip()
        return None

    def extract_postcode(self, address):
        if not address:
            return None
        match = re.search(r"\b\d{4}\b", address)
        return match.group(0) if match else None

    def parse_website_image(self, response):
        item = response.meta["item"]

        images = response.css("img::attr(src)").getall()
        if images:
            # Convert relative URLs to absolute
            from urllib.parse import urljoin
            images = [urljoin(response.url, img) for img in images]

            # Try to pick large images based on filename hints
            large_images = [
                img for img in images if any(x in img.lower() for x in ["large", "full", "max", "original"])
            ]
            if large_images:
                item["image_url"] = large_images[0]
            else:
                # fallback: just take the first image
                item["image_url"] = images[0]
        else:
            # fallback to SerpAPI if no images found
            place = item.get("place_data")  # pass the place JSON in the item earlier
            item["image_url"] = self.get_serpapi_image(place)

        yield item

    def get_serpapi_image(self, place):
        # Fallback to SerpAPI images
        if "photos" in place and place["photos"]:
            photo_ref = place["photos"][0].get("photo_reference")
            if photo_ref:
                return f"https://maps.googleapis.com/maps/api/place/photo?maxwidth=1600&photoreference={photo_ref}&key=YOUR_GOOGLE_API_KEY"

        # fallback to thumbnail if no photos
        return place.get("thumbnail")

