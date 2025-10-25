import scrapy
from scrapy_playwright.page import PageMethod
import re

class KidsbookSpider(scrapy.Spider):
    name = 'kidsbook'
    
    # List of locations to search in
    LOCATIONS = [
        'vic',
        # 'nsw',
        # 'qld',
        # 'sa',
        # 'wa'
    ]
    
    # List of categories to search in
    CATEGORIES = [
        'art',
        'dance',
        'drama',
        'martialarts',
        'music',
        'stem',
        'sport',
        'tutoring',
        'wellbeing'
    ]
    
    def start_requests(self):
        """
        Generate requests for each category/location combination
        """
        for category in self.CATEGORIES:
            for location in self.LOCATIONS:
                url = f'https://kidsbook.com.au/find/{category}/{location}'
                yield scrapy.Request(
                    url=url,
                    meta={
                        "playwright": True,
                        "playwright_include_page": True,
                        "category": category,
                        "location": location
                    },
                    callback=self.parse_category_page,
                    errback=self.errback_handler,
                )
    
    async def parse_category_page(self, response):
        """
        Extract provider links from category pages and handle pagination
        """
        page = response.meta["playwright_page"]
        category = response.meta["category"]
        location = response.meta["location"]
        
        try:
            self.logger.info(f"Extracting provider links from: {response.url}")
            
            # Wait for page to load
            await page.wait_for_load_state("domcontentloaded")
            await page.wait_for_load_state("networkidle")
            
            # Process all pages
            page_num = 1
            has_next_page = True
            
            while has_next_page:
                self.logger.info(f"Processing page {page_num} of {category}/{location}")
                
                # Wait for content to fully load
                await page.wait_for_selector('a[href^="/p/"]', state="attached", timeout=10000)
                
                # Find provider elements directly on the page
                provider_elements = await page.query_selector_all('a[href^="/p/"]')
                
                if provider_elements:
                    self.logger.info(f"Found {len(provider_elements)} provider links on page {page_num}")
                    
                    # Extract provider URLs
                    provider_urls = []
                    for element in provider_elements:
                        href = await element.get_attribute('href')
                        if href:
                            full_url = response.urljoin(href)
                            if full_url not in provider_urls:
                                provider_urls.append(full_url)
                    
                    # Process each provider URL
                    for provider_url in provider_urls:
                        yield scrapy.Request(
                            url=provider_url,
                            meta={
                                "playwright": True,
                                "playwright_include_page": True,
                                "category": category,
                                "location": location
                            },
                            callback=self.parse_provider_detail,
                            errback=self.errback_handler,
                        )
                else:
                    self.logger.info(f"No provider links found on page {page_num}")
                
                # Check for next page button
                next_button = await page.query_selector('button.chakra-button[aria-label="Next Page"]')
                
                if next_button:
                    # Check if the next button is disabled
                    is_disabled = await page.evaluate('button => button.hasAttribute("disabled")', next_button)
                    
                    if not is_disabled:
                        self.logger.info(f"Clicking 'Next Page' button to go to page {page_num + 1}")
                        await next_button.click()
                        await page.wait_for_load_state("networkidle")
                        await page.wait_for_timeout(2000)  # Wait for page to settle
                        page_num += 1
                    else:
                        self.logger.info("Next Page button is disabled, no more pages")
                        has_next_page = False
                else:
                    self.logger.info("No Next Page button found")
                    has_next_page = False
            
            await page.close()
            
        except Exception as e:
            self.logger.error(f"Error in parse_category_page: {str(e)}")
            try:
                await page.close()
            except:
                pass
    
    async def parse_provider_detail(self, response):
        """
        Extract provider details from the provider page
        """
        page = response.meta["playwright_page"]
        category = response.meta.get("category", "unknown")
        
        try:
            # Wait for page to load
            await page.wait_for_load_state("domcontentloaded")
            await page.wait_for_load_state("networkidle")
            
            # Extract provider name
            provider_name_element = await page.query_selector('h1[class*="chakra-heading"]')
            provider_name = await provider_name_element.inner_text() if provider_name_element else "Unknown"
            
            # Initialize contact details
            phone = ""
            email = ""
            website = ""
            
            # ===== PHONE NUMBER EXTRACTION =====
            # Use app.kidsbook.io for ?phone=yes
            current_url = page.url
            phone_url = current_url.replace("https://kidsbook.com.au", "https://app.kidsbook.io").split('?')[0] + "?phone=yes"
            
            self.logger.info(f"Trying direct URL approach with ?phone=yes for {provider_name}")
            await page.goto(phone_url)
            await page.wait_for_load_state("networkidle")
            await page.wait_for_timeout(2000)  # Additional delay for JS to render popup
            
            # Wrap popup extraction in try-except to handle timeout
            try:
                # Wait for the phone link to appear
                await page.wait_for_selector('a[href^="tel:"]', state="attached", timeout=10000)
                
                # Extract phone from the link
                phone_element = await page.query_selector('a[href^="tel:"]')
                if phone_element:
                    phone_href = await phone_element.get_attribute('href')
                    phone = phone_href.replace('tel:', '').strip()
                    self.logger.info(f"Found phone number from tel link: {phone}")
            except Exception as popup_err:
                self.logger.warning(f"Phone link wait failed: {str(popup_err)}. Falling back to page text.")
            
            # Fallback to page text if no phone found
            if not phone:
                page_text = await page.evaluate('() => document.body.innerText')
                phone_matches = re.findall(r'0\d{3}\s?\d{3}\s?\d{3}|0\d{1}\s?\d{4}\s?\d{4}|0\d{4}\s?\d{3}\s?\d{3}', page_text)
                for match in phone_matches:
                    if match != "0512640919":
                        phone = match
                        self.logger.info(f"Found phone number in page text: {phone}")
                        break
            
            # Go back to original URL for other extractions
            await page.goto(current_url)
            await page.wait_for_load_state("networkidle")
            
            # ===== EMAIL EXTRACTION =====
            # Look for email in the page
            email_element = await page.query_selector('a[href^="mailto:"]')
            if email_element:
                email_href = await email_element.get_attribute('href')
                if email_href:
                    email = email_href.replace('mailto:', '')
                    self.logger.info(f"Found email: {email}")
            
            # If email not found, try regex pattern in content
            if not email:
                content = await page.content()
                email_matches = re.findall(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', content)
                if email_matches:
                    # Filter common system emails
                    valid_emails = [e for e in email_matches if not e.endswith(('.png', '.jpg', '.gif'))]
                    if valid_emails:
                        email = valid_emails[0]
                        self.logger.info(f"Extracted email from text: {email}")
            
            # ===== WEBSITE EXTRACTION =====
            # Extract actual website (not tracking pixels or maps)
            all_links = await page.query_selector_all('a')
            for link in all_links:
                href = await link.get_attribute('href')
                
                # Skip missing links, internal kidsbook links, and social media
                if not href or 'kidsbook' in href or 'facebook.com' in href or 'instagram.com' in href:
                    continue
                    
                # Skip Facebook tracking pixels and Google Maps links
                if 'facebook.com/tr' in href or 'google.com/maps' in href:
                    continue
                    
                # Skip tel and mailto links
                if href.startswith('tel:') or href.startswith('mailto:'):
                    continue
                
                # Found a valid external website
                if href.startswith('http'):
                    website = href
                    self.logger.info(f"Found actual website: {website}")
                    break
            
            # Extract addresses and parse them into components
            address_entries = []
            try:
                # Look for paragraphs containing address information
                address_elements = await page.query_selector_all('p.chakra-text')
                for elem in address_elements:
                    text = await elem.inner_text()
                    # Look for patterns typically found in addresses
                    if text and ("VIC" in text or "Victoria" in text) and (re.search(r'\b\d{4}\b', text) or re.search(r'\bSt\b|\bRd\b|\bAve\b|\bDr\b', text)):
                        # Skip elements that are clearly not addresses
                        if len(text) > 200:  # Too long to be an address
                            continue
                            
                        # Parse address into components
                        address_text = text.strip()
                        
                        # Extract suburb and postcode
                        suburb_postcode_match = re.search(r'([A-Za-z\s]+)\s+VIC\s+(\d{4})', address_text)
                        
                        if suburb_postcode_match:
                            suburb = suburb_postcode_match.group(1).strip()
                            postcode = suburb_postcode_match.group(2).strip()
                            
                            # Extract street address (everything before suburb)
                            street_address = address_text[:suburb_postcode_match.start()].strip()
                            if street_address.endswith(','):
                                street_address = street_address[:-1].strip()
                            
                            address_entries.append({
                                'full_address': address_text,
                                'street_address': street_address,
                                'suburb': suburb,
                                'postcode': postcode,
                                'state': 'VIC'
                            })
                        else:
                            # If we can't parse it, just store the full address
                            address_entries.append({
                                'full_address': address_text,
                                'street_address': '',
                                'suburb': '',
                                'postcode': '',
                                'state': 'VIC'
                            })
            except Exception as e:
                self.logger.error(f"Error extracting addresses: {str(e)}")
            
            # Extract features/services
            features = []
            try:
                feature_elements = await page.query_selector_all('p.chakra-text')
                for elem in feature_elements:
                    text = await elem.inner_text()
                    if text and text.strip().startswith("â€¢"):
                        features.append(text.strip())
            except Exception as e:
                self.logger.error(f"Error extracting features: {str(e)}")
            
            # Extract description - improved approach
            description = ""
            try:
                # Look for paragraphs that appear to be descriptions
                all_paragraphs = await page.query_selector_all('p.chakra-text')
                for elem in all_paragraphs:
                    text = await elem.inner_text()
                    # Skip elements that are likely features, addresses, or short text
                    if (text and len(text) > 50 and 
                        not text.strip().startswith("â€¢") and 
                        not re.search(r'\b\d{4}\b', text)):
                        description += text.strip() + " "
            except Exception as e:
                self.logger.error(f"Error extracting description: {str(e)}")
            
            # Log successful extraction
            self.logger.info(f"Scraped: {provider_name.strip()}")
            
            # Create data item with additional fields
            yield {
                'provider_name': provider_name.strip(),
                'provider_url': response.url.split('?')[0],
                'category': category,
                'contact': {
                    'phone': phone,
                    'email': email,
                    'website': website
                },
                'addresses': address_entries,
                'features': features,
                'description': description.strip()
            }
            
            await page.close()
            
        except Exception as e:
            self.logger.error(f"Error in parse_provider_detail: {str(e)}")
            try:
                await page.close()
            except:
                pass
    
    async def errback_handler(self, failure):
        """
        Handle any errors during the request
        """
        page = failure.request.meta.get("playwright_page")
        if page:
            await page.close()
            
        self.logger.error(f"Request failed: {failure.request.url}")
        self.logger.error(f"Error details: {str(failure.value)}")