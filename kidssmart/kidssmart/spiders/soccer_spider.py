import scrapy

class SoccerSpider(scrapy.Spider):
    name = "soccer"
    allowed_domains = ["soccer5s.com"]
    start_urls = ["https://dandenong.soccer5s.com/"]

    def parse(self, response):
        # Extract basic sections from the home page
        for section in response.css("div.elementor-widget-container"):
            title = section.css("h2::text, h3::text").get(default="").strip()
            paragraph = section.css("p::text").get(default="").strip()

            if title or paragraph:
                yield {
                    "title": title,
                    "description": paragraph,
                    "url": response.url
                }

        # Follow links to other pages like leagues, events, etc.
        for link in response.css("a::attr(href)").getall():
            if "soccer5s.com" in link or link.startswith("/"):
                yield response.follow(link, self.parse_page)

    def parse_page(self, response):
        # Extract more detailed info from subpages
        page_title = response.css("h1::text, h2::text").get(default="").strip()
        paragraphs = " ".join(response.css("p::text").getall()).strip()

        if page_title or paragraphs:
            yield {
                "page_title": page_title,
                "content": paragraphs,
                "url": response.url
            }
