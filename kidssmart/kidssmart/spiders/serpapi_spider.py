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
        "AUTOTHROTTLE_ENABLED": True,
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

        # Create specific search queries from using the categories listed
        base_location = "Melbourne"
        for category, keywords in self.CATEGORIES.items():
            for keyword in keywords:
                query = f"{keyword} classes for kids in {base_location}"
                url = (
                    f"https://serpapi.com/search.json?"
                    f"engine=google_maps&q={query}&type=search&api_key={api_key}"
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

            item["description"] = (
                    place.get("description")
                    or place.get("snippet")
                    or "Kids-related activity or venue")
            item["phone"] = place.get("phone")
            item["website"] = place.get("website")
            item["email"] = None
            item["age_range"] = None
            item["cost"] = None
            item["schedule"] = None
            item["image_url"] = place.get("images")
            item["source_url"] = place.get("links", {}).get("website")

            if item["source_url"] and not item["source_url"].startswith("http"):
                item["source_url"] = None

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
        """Extract additional info like description, email, schedule, cost, and age range."""
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

        yield item

    def extract_suburb(self, address):
        """Extract suburb name from address (Australian-style)."""
        if not address:
            return None
        parts = address.split(",")
        if len(parts) > 1:
            return parts[-2].strip()
        return None

    def extract_postcode(self, address):
        """Extract 4-digit postcode from address."""
        if not address:
            return None
        match = re.search(r"\b\d{4}\b", address)
        return match.group(0) if match else None
