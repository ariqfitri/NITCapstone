import scrapy
from scrapy_playwright.page import PageMethod
import re
import json

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
    
    # List of categories to search in - only first one enabled for testing
    CATEGORIES = [
        'art',
        # 'dance',
        # 'drama',
        # 'martialarts',
        # 'music',
        # 'stem',
        # 'sport',
        # 'tutoring',
        # 'wellbeing'
    ]
    
    def start_requests(self):
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
        page = response.meta["playwright_page"]
        category = response.meta["category"]
        location = response.meta["location"]
        
        try:
            if response.status == 429:
                self.logger.warning(f"Rate limited for {response.url}")
                await page.close()
                return
                
            self.logger.info(f"Extracting provider links from: {response.url}")
            
            await page.wait_for_load_state("domcontentloaded")
            await page.wait_for_load_state("networkidle")
            
            page_num = 1
            has_next_page = True
            
            while has_next_page:
                self.logger.info(f"Processing page {page_num} of {category}/{location}")
                
                await page.wait_for_selector('a[href^="/p/"]', state="attached", timeout=10000)
                provider_elements = await page.query_selector_all('a[href^="/p/"]')
                
                if provider_elements:
                    self.logger.info(f"Found {len(provider_elements)} provider links on page {page_num}")
                    
                    provider_urls = []
                    for element in provider_elements:
                        href = await element.get_attribute('href')
                        if href:
                            full_url = response.urljoin(href)
                            if full_url not in provider_urls:
                                provider_urls.append(full_url)
                    
                    for provider_url in provider_urls:
                        yield scrapy.Request(
                            url=provider_url,
                            meta={
                                "playwright": True,
                                "playwright_include_page": True,
                                "category": category
                            },
                            callback=self.parse_provider_detail,
                            errback=self.errback_handler,
                        )
                
                try:
                    next_button = await page.query_selector('button[aria-label="Next Page"]')
                    if next_button:
                        is_disabled = await next_button.get_attribute('disabled')
                        if not is_disabled:
                            self.logger.info(f"Clicking Next Page button for page {page_num + 1}")
                            await next_button.click()
                            await page.wait_for_load_state("networkidle")
                            page_num += 1
                        else:
                            self.logger.info("Next Page button is disabled")
                            has_next_page = False
                    else:
                        self.logger.info("No Next Page button found")
                        has_next_page = False
                except Exception as e:
                    self.logger.error(f"Error checking pagination: {str(e)}")
                    has_next_page = False
            
            await page.close()
            
        except Exception as e:
            self.logger.error(f"Error in parse_category_page: {str(e)}")
            try:
                await page.close()
            except:
                pass
    
    async def parse_provider_detail(self, response):
        page = response.meta["playwright_page"]
        category = response.meta.get("category", "unknown")
        
        try:
            if response.status == 429:
                self.logger.warning(f"Rate limited for provider {response.url}")
                await page.close()
                return
                
            await page.wait_for_load_state("domcontentloaded")
            await page.wait_for_load_state("networkidle")
            
            # Extract provider name
            provider_name_element = await page.query_selector('h1.chakra-heading')
            provider_name = await provider_name_element.inner_text() if provider_name_element else "Unknown"
            
            phone = ""
            email = ""
            website = ""
            
            # ===== PHONE EXTRACTION (WORKING - DON'T CHANGE) =====
            phone_link = await page.query_selector('a[href*="?phone=yes"]')
            if phone_link:
                phone_text = await phone_link.inner_text()
                phone_match = re.search(r'([\d\s]+)', phone_text)
                if phone_match:
                    partial_phone = phone_match.group(1).strip()
                    self.logger.info(f"Found partial phone: {partial_phone}")
                    
                    try:
                        await phone_link.click()
                        await page.wait_for_timeout(3000)
                        
                        popup_selectors = [
                            'div.bubble-element.Popup',
                            'div[class*="Popup"]', 
                            'div[role="dialog"]',
                            '.chakra-modal__content',
                            'div[class*="modal"]'
                        ]
                        
                        phone_found = False
                        for selector in popup_selectors:
                            popups = await page.query_selector_all(selector)
                            for popup in popups:
                                popup_text = await popup.inner_text()
                                if popup_text:
                                    self.logger.info(f"Checking popup: {popup_text[:50]}...")
                                    full_phone_match = re.search(r'0[1-9]\d{8}', popup_text)
                                    if full_phone_match:
                                        phone = full_phone_match.group(0)
                                        phone_found = True
                                        self.logger.info(f"Found full phone in popup: {phone}")
                                        break
                            if phone_found:
                                break
                        
                        if not phone_found:
                            tel_element = await page.query_selector('a[href^="tel:"]')
                            if tel_element:
                                tel_href = await tel_element.get_attribute('href')
                                phone = tel_href.replace('tel:', '').strip()
                                phone_found = True
                                self.logger.info(f"Found full phone from tel link: {phone}")
                        
                        if not phone_found:
                            phone = partial_phone
                            self.logger.info(f"Using partial phone: {phone}")
                            
                    except Exception as phone_err:
                        self.logger.warning(f"Could not get full phone: {str(phone_err)}")
                        phone = partial_phone
            
            # ===== EMAIL EXTRACTION =====
            email_element = await page.query_selector('a[href^="mailto:"]')
            if email_element:
                email_href = await email_element.get_attribute('href')
                if email_href:
                    email = email_href.replace('mailto:', '')
                    self.logger.info(f"Found email: {email}")
            
            if not email:
                content = await page.content()
                email_matches = re.findall(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', content)
                if email_matches:
                    valid_emails = [e for e in email_matches if not any(x in e.lower() for x in 
                        ['noreply', 'no-reply', 'support', 'admin', 'info@kidsbook', 'contact@kidsbook'])]
                    if valid_emails:
                        email = valid_emails[0]
                        self.logger.info(f"Found email: {email}")
            
            # ===== WEBSITE EXTRACTION =====
            external_links = await page.query_selector_all('a[href^="http"]')
            for link in external_links:
                href = await link.get_attribute('href')
                if (href and 
                    'kidsbook.com' not in href and 
                    'app.kidsbook.io' not in href and  # BLOCK KIDSBOOK APP LINKS
                    'twitter.com' not in href and 
                    'facebook.com' not in href and 
                    'instagram.com' not in href and
                    'google.com/maps' not in href and
                    'google.com/intl' not in href):
                    website = href
                    self.logger.info(f"Found website: {website}")
                    break
            
            # ===== ADDRESS EXTRACTION (WITH DEBUGGING) =====
            address_entries = []
            try:
                self.logger.info("Starting address extraction...")
                
                # First check what elements exist
                all_paragraphs = await page.query_selector_all('p')
                self.logger.info(f"Found {len(all_paragraphs)} total paragraph elements")
                
                chakra_paragraphs = await page.query_selector_all('p.chakra-text')
                self.logger.info(f"Found {len(chakra_paragraphs)} p.chakra-text elements")
                
                # Check if any paragraphs contain VIC
                vic_count = 0
                for elem in all_paragraphs:
                    text = await elem.inner_text()
                    if text and "VIC" in text:
                        vic_count += 1
                        self.logger.info(f"Found VIC text: {text[:100]}...")
                
                self.logger.info(f"Found {vic_count} paragraphs containing VIC")
                
                # Original address extraction logic
                address_elements = await page.query_selector_all('p.chakra-text')
                for elem in address_elements:
                    text = await elem.inner_text()
                    if text and ("VIC" in text or "Victoria" in text) and (re.search(r'\b\d{4}\b', text) or re.search(r'\bSt\b|\bRd\b|\bAve\b|\bDr\b', text)):
                        if len(text) > 200:
                            continue
                            
                        self.logger.info(f"Processing potential address: {text}")
                        address_text = text.strip()
                        suburb_postcode_match = re.search(r'([A-Za-z\s]+)\s+VIC\s+(\d{4})', address_text)
                        
                        if suburb_postcode_match:
                            suburb = suburb_postcode_match.group(1).strip()
                            postcode = suburb_postcode_match.group(2).strip()
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
                            self.logger.info(f"Successfully parsed address: {address_text}")
                        else:
                            address_entries.append({
                                'full_address': address_text,
                                'street_address': '',
                                'suburb': '',
                                'postcode': '',
                                'state': 'VIC'
                            })
                            self.logger.info(f"Added unparsed address: {address_text}")
                        break
            except Exception as e:
                self.logger.error(f"Error extracting addresses: {str(e)}")

            if not address_entries:
                self.logger.info("No addresses found, adding empty entry")
                address_entries.append({
                    'full_address': '',
                    'street_address': '',
                    'suburb': '',
                    'postcode': '',
                    'state': 'VIC'
                })
            
            # ===== FEATURES =====
            features = []
            try:
                feature_elements = await page.query_selector_all('p.chakra-text')
                for elem in feature_elements:
                    text = await elem.inner_text()
                    if text and text.strip().startswith("♪"):
                        features.append(text.strip())
            except Exception as e:
                self.logger.error(f"Error extracting features: {str(e)}")
            
            # ===== DESCRIPTION =====
            description = ""
            try:
                all_paragraphs = await page.query_selector_all('p.chakra-text')
                for elem in all_paragraphs:
                    text = await elem.inner_text()
                    if (text and len(text) > 50 and 
                        not text.strip().startswith("♪") and 
                        not re.search(r'\b\d{4}\b', text)):
                        description += text.strip() + " "
            except Exception as e:
                self.logger.error(f"Error extracting description: {str(e)}")
            
            self.logger.info(f"Scraped: {provider_name.strip()}")
            self.logger.info(f"  Phone: {phone}")
            self.logger.info(f"  Email: {email}")
            self.logger.info(f"  Website: {website}")
            self.logger.info(f"  Address: {address_entries[0]['full_address'] if address_entries else 'None'}")
            
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
        page = failure.request.meta.get("playwright_page")
        if page:
            await page.close()
        
        error_msg = str(failure.value)
        if "429" in error_msg:
            self.logger.warning(f"Rate limit detected for {failure.request.url} - will retry")
        else:
            self.logger.error(f"Request failed: {failure.request.url}")
            self.logger.error(f"Error details: {error_msg}")