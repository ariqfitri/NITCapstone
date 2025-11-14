import scrapy

class ArtSpider(scrapy.Spider):
    name = "art"
    allowed_domains = ["kidspot.com.au"]
    start_urls = ["https://www.kidspot.com.au/things-to-do/activities/art-and-craft"]

    def parse(self, response):
        for activity in response.css("article.activity-card"):
            title = activity.css("h3.card-title::text").get(default="").strip()
            description = activity.css("p.card-description::text").get(default="").strip()
            link = activity.css("a::attr(href)").get()

            # Filter only art-related activities
            if any(word in title.lower() for word in ["art", "paint", "craft", "drawing", "creative", "design"]):
                yield {
                    "title": title,
                    "description": description,
                    "url": response.urljoin(link),
                    "source": "Kidspot Art Activities"
                }

        # Pagination
        next_page = response.css("a.pagination__next::attr(href)").get()
        if next_page:
            yield response.follow(next_page, self.parse)
