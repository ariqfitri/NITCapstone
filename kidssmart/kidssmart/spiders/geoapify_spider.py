import os
import scrapy
import json
from ..items import KidssmartItem

class GeoapifySpider(scrapy.Spider):
    name = "geoapify"
    allowed_domains = ["api.geoapify.com"]

    def start_requests(self):
        api_key = os.getenv("GEOAPIFY_API_KEY")
        if not api_key:
            self.logger.error("GEOAPIFY_API_KEY is not set in environment variables.")
            return

        # Major Australian cities
        cities = ["Melbourne"
                  #"Sydney",
                  #"Brisbane",
                  #"Perth",
                  #"Adelaide"
                  ]

        # Kids-related categories
        categories = [
            "leisure.playground",
            "activity.sports_centre",
            "education.school",
            "entertainment.museum",
            "leisure.park",
            "childcare.kindergarten",
            "childcare.nursery",
            "entertainment.cinema",
        ]

        # Central coordinates for each city (geoapify uses lat long)
        city_coords = {
            "Melbourne": (144.9631, -37.8136),
            "Sydney": (151.2093, -33.8688),
            "Brisbane": (153.0251, -27.4698),
            "Perth": (115.8575, -31.9505),
            "Adelaide": (138.6007, -34.9285),
        }

        for city in cities:
            lon, lat = city_coords[city]
            for category in categories:
                url = (
                    f"https://api.geoapify.com/v2/places?"
                    f"categories={category}"
                    f"&filter=circle:{lon},{lat},8000"
                    f"&limit=100"
                    f"&apiKey={api_key}"
                )
                yield scrapy.Request(url, callback=self.parse, meta={"city": city, "category": category})

    def parse(self, response):
        try:
            data = json.loads(response.text)
        except json.JSONDecodeError:
            self.logger.error("Failed to parse JSON response.")
            return

        features = data.get("features", [])
        if not features:
            self.logger.warning(
                f"No results found for {response.meta.get('category')} in {response.meta.get('city')}"
            )
            return

        for feature in features:
            props = feature.get("properties", {})
            item = KidssmartItem()

            item["title"] = props.get("name")
            item["address"] = props.get("formatted")
            item["suburb"] = props.get("suburb")
            item["postcode"] = props.get("postcode")
            item["activity_type"] = response.meta.get("category")  #fixed key
            item["source_url"] = props.get("datasource", {}).get("raw", {}).get("url")
            item["description"] = (
                props.get("details", "Family and kids-friendly activity location")
            )
            item["image"] = None  # Geoapify doesn’t include images

            if item["title"] and item["address"]:
                yield item