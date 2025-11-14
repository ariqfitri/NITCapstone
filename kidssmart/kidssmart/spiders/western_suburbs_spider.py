# western_suburbs_dynamic_fixed.py
import scrapy
from scrapy_playwright.page import PageMethod
import re
import asyncio
from urllib.parse import urlparse

class WesternSuburbsDynamicSpider(scrapy.Spider):
    name = 'western_suburbs'
    
    # JUST URLs - everything else will be extracted dynamically
    PROVIDERS = [
        {'url': 'https://wildatartkids.com.au/'},
        {'url': 'https://www.codecamp.com.au/'},
        {'url': 'https://www.bkgymswim.com.au/bks-gymnastics-caroline-springs/'},
        {'url': 'https://samuraikarate.com/sunshine-dojo-vic/'},
        {'url': 'https://www.gymnastics-unlimited.com.au/'},
        {'url': 'https://www.pointcookdance.com.au/'},
        {'url': 'https://www.brooksschoolofdance.com/'},
        {'url': 'https://www.martialjourney.com/'}
    ]
    
    def start_requests(self):
        """Generate requests for each provider URL"""
        for provider in self.PROVIDERS:
            yield scrapy.Request(
                url=provider['url'],
                meta={
                    "playwright": True,
                    "playwright_include_page": True,
                    "playwright_context_kwargs": {
                        "ignore_https_errors": True,
                    },
                    "playwright_page_methods": [
                        PageMethod("wait_for_load_state", "domcontentloaded"),
                        PageMethod("wait_for_load_state", "networkidle", timeout=15000),
                    ],
                    "provider_url": provider['url'],
                    "playwright_page_goto_kwargs": {
                        "wait_until": "domcontentloaded",
                        "timeout": 30000
                    }
                },
                callback=self.parse_provider_detail,
                errback=self.errback_handler,
            )
    
    async def parse_provider_detail(self, response):
        """
        Dynamically extract ALL data from the website
        """
        page = response.meta["playwright_page"]
        provider_url = response.meta.get("provider_url", response.url)
        
        try:
            # Dynamically extract provider name
            provider_name = await self.extract_provider_name_dynamic(page, provider_url)
            
            # Dynamically detect category based on content
            category = await self.detect_category_dynamic(page, provider_url)
            
            # Extract contact details
            phone = await self.extract_phone_flexible(page)
            email = await self.extract_email_flexible(page)
            website = provider_url
            
            # Extract addresses
            address_entries = await self.extract_addresses_improved(page, provider_url)
            
            # Extract features/programs
            features = await self.extract_features_dynamic(page)
            
            # Extract description
            description = await self.extract_description_dynamic(page)
            
            # Log successful extraction
            self.logger.info(f"Successfully scraped: {provider_name}")
            
            # Create data item
            yield {
                'provider_name': provider_name,
                'provider_url': provider_url,
                'category': category,
                'contact': {
                    'phone': phone,
                    'email': email,
                    'website': website
                },
                'addresses': address_entries,
                'features': features,
                'description': description
            }
            
            await page.close()
            
        except Exception as e:
            self.logger.error(f"Error scraping {provider_url}: {str(e)}")
            try:
                await page.close()
            except:
                pass
    
    async def extract_provider_name_dynamic(self, page, url):
        """Dynamically extract provider name using multiple strategies"""
        try:
            # Strategy 1: Try business-specific selectors first
            business_selectors = [
                'h1',
                '.business-name',
                '.company-name', 
                '.site-title',
                '.logo-text',
                '.header-title',
                '.navbar-brand',
                '.hero-title',
                '.page-title',
                'title'
            ]
            
            for selector in business_selectors:
                try:
                    element = await page.query_selector(selector)
                    if element:
                        name = await element.inner_text()
                        if name and self.is_valid_business_name(name):
                            cleaned_name = self.clean_business_name(name, url)
                            if cleaned_name:
                                return cleaned_name
                except:
                    continue
            
            # Strategy 2: Try meta tags
            try:
                meta_title = await page.query_selector('title')
                if meta_title:
                    title_text = await meta_title.inner_text()
                    if title_text and self.is_valid_business_name(title_text):
                        cleaned_name = self.clean_business_name(title_text, url)
                        if cleaned_name:
                            return cleaned_name
                
                # Try OpenGraph and Schema.org
                meta_og = await page.query_selector('meta[property="og:site_name"]')
                if meta_og:
                    og_name = await meta_og.get_attribute('content')
                    if og_name and self.is_valid_business_name(og_name):
                        return self.clean_business_name(og_name, url)
            except:
                pass
            
            # Strategy 3: Extract from URL as fallback
            parsed_url = urlparse(url)
            domain_name = parsed_url.netloc.replace('www.', '').split('.')[0]
            if domain_name and len(domain_name) > 2:
                return domain_name.replace('-', ' ').title()
            
            # Strategy 4: Use domain name with TLD
            domain_parts = parsed_url.netloc.replace('www.', '').split('.')
            if len(domain_parts) >= 2:
                return domain_parts[0].replace('-', ' ').title()
            
            return "Unknown Provider"
            
        except Exception as e:
            self.logger.error(f"Error extracting provider name: {str(e)}")
            return "Unknown Provider"
    
    async def detect_category_dynamic(self, page, url):
        """Dynamically detect category based on page content"""
        try:
            # Get page content for analysis
            page_text = await page.evaluate('() => document.body.innerText')
            page_content = await page.content()
            
            text_lower = page_text.lower()
            content_lower = page_content.lower()
            
            # Category keywords with weights
            category_patterns = {
                'art': ['art', 'painting', 'drawing', 'creative', 'craft', 'pottery', 'studio', 'canvas'],
                'dance': ['dance', 'ballet', 'jazz', 'hip hop', 'contemporary', 'tap', 'choreography'],
                'sport': ['sport', 'gymnastics', 'fitness', 'training', 'coaching', 'exercise', 'athletic'],
                'martialarts': ['martial arts', 'karate', 'taekwondo', 'judo', 'self defense', 'self-defence', 'belt', 'dojo'],
                'stem': ['coding', 'programming', 'robotics', 'stem', 'technology', 'computer', 'science', 'engineering'],
                'music': ['music', 'piano', 'guitar', 'violin', 'singing', 'voice', 'instrument'],
                'swimming': ['swim', 'pool', 'aquatic', 'water safety', 'diving']
            }
            
            category_scores = {category: 0 for category in category_patterns}
            
            # Score each category based on keyword matches
            for category, keywords in category_patterns.items():
                for keyword in keywords:
                    if keyword in text_lower:
                        category_scores[category] += 1
            
            # Also check URL for clues
            url_lower = url.lower()
            for category, keywords in category_patterns.items():
                for keyword in keywords:
                    if keyword.replace(' ', '') in url_lower:
                        category_scores[category] += 2  # Higher weight for URL matches
            
            # Find the category with highest score
            best_category = max(category_scores, key=category_scores.get)
            
            # Only return if we have reasonable confidence
            if category_scores[best_category] > 0:
                return best_category
            else:
                return 'general'
                
        except Exception as e:
            self.logger.error(f"Error detecting category: {str(e)}")
            return 'general'
    
    async def extract_features_dynamic(self, page):
        """Dynamically extract features/programs from page content"""
        features = []
        
        try:
            # Get page text
            page_text = await page.evaluate('() => document.body.innerText')
            text_lower = page_text.lower()
            
            # Comprehensive feature keywords
            feature_keywords = [
                'after school', 'holiday programs', 'birthday parties', 'private lessons',
                'beginner', 'intermediate', 'advanced', 'trial class', 'free trial',
                'qualified instructors', 'small classes', 'term classes', 'daytime classes',
                'weekend classes', 'group lessons', 'individual tuition', 'workshops',
                'summer camp', 'winter program', 'school holiday', 'enrichment program',
                'certified', 'licensed', 'accredited', 'experienced teachers'
            ]
            
            # Activity-specific features
            activity_features = [
                'art classes', 'drawing', 'painting', 'creative', 'craft', 'pottery',
                'ballet', 'jazz', 'hip hop', 'contemporary', 'tap', 'dance classes',
                'gymnastics', 'sports', 'fitness', 'training', 'coaching',
                'karate', 'martial arts', 'self defense', 'self-defence',
                'coding', 'programming', 'robotics', 'stem', 'technology',
                'music lessons', 'piano', 'guitar', 'singing', 'violin',
                'swimming lessons', 'water safety', 'diving'
            ]
            
            # Combine all keywords
            all_keywords = feature_keywords + activity_features
            
            # Look for matches
            for keyword in all_keywords:
                if keyword in text_lower:
                    feature_text = f"• {keyword.title()}"
                    if feature_text not in features:
                        features.append(feature_text)
            
            # Look for age ranges with better pattern matching
            age_patterns = [
                r'ages?\s*(\d+)[-–to\s]+(\d+)',
                r'for\s+(\d+)[-–]\s*year',
                r'(\d+)\s*-\s*(\d+)\s*years',
                r'ages?\s*(\d+)\s*and\s*up'
            ]
            
            for pattern in age_patterns:
                age_matches = re.findall(pattern, text_lower)
                for match in age_matches:
                    if len(match) == 2:
                        age_feature = f"• Ages {match[0]}-{match[1]}"
                    else:
                        age_feature = f"• Ages {match[0]}+"
                    if age_feature not in features:
                        features.append(age_feature)
            
            # Look for price/cost information
            price_matches = re.findall(r'\$\d+', page_text)
            if price_matches:
                features.append("• Various pricing options available")
            
            # Limit features and ensure uniqueness
            return list(set(features))[:15]
            
        except Exception as e:
            self.logger.error(f"Error extracting features: {str(e)}")
            return []
    
    async def extract_description_dynamic(self, page):
        """Dynamically extract description using multiple strategies"""
        try:
            # Strategy 1: Meta description
            try:
                meta_desc = await page.query_selector('meta[name="description"]')
                if meta_desc:
                    content = await meta_desc.get_attribute('content')
                    if content and len(content.strip()) > 20:
                        return content.strip()[:300]
            except:
                pass
            
            # Strategy 2: OpenGraph description
            try:
                meta_og = await page.query_selector('meta[property="og:description"]')
                if meta_og:
                    content = await meta_og.get_attribute('content')
                    if content and len(content.strip()) > 20:
                        return content.strip()[:300]
            except:
                pass
            
            # Strategy 3: Look for description sections
            desc_selectors = [
                '.hero-text', '.intro', '.description', '.about',
                '.content p', '.main p', 'main p', '.entry-content p',
                '.lead', '.subtitle', '.tagline', '.summary',
                '[class*="description"]', '[class*="about"]', '[class*="intro"]'
            ]
            
            for selector in desc_selectors:
                try:
                    elements = await page.query_selector_all(selector)
                    for element in elements:
                        text = await element.inner_text()
                        if text and len(text.strip()) > 30 and len(text.strip()) < 400:
                            # Skip if it looks like navigation or contact info
                            text_lower = text.lower()
                            skip_indicators = ['menu', 'home', 'contact', 'phone', 'email', 'address', 'login', 'sign up']
                            if not any(skip in text_lower for skip in skip_indicators):
                                return text.strip()[:300]
                except:
                    continue
            
            # Strategy 4: First meaningful paragraph
            try:
                paragraphs = await page.query_selector_all('p')
                for p in paragraphs:
                    text = await p.inner_text()
                    if (text and len(text.strip()) > 50 and 
                        len(text.strip()) < 400 and
                        not text.strip().startswith('©') and
                        'cookie' not in text.lower() and
                        'privacy' not in text.lower() and
                        'terms' not in text.lower()):
                        return text.strip()[:300]
            except:
                pass
            
            # Strategy 5: Generate from page title and category
            try:
                title = await page.query_selector('title')
                if title:
                    title_text = await title.inner_text()
                    return f"{self.clean_business_name(title_text, '')} - providing quality programs and activities."
            except:
                pass
            
            return "Quality programs and activities for children and families."
            
        except Exception as e:
            self.logger.error(f"Error extracting description: {str(e)}")
            return "Children's activity provider offering various programs and services."

    def is_valid_business_name(self, name):
        """Check if the extracted name is a valid business name"""
        if not name or len(name.strip()) < 2:
            return False
        
        name_lower = name.lower()
        
        # Skip common page titles
        invalid_indicators = [
            'welcome to', 'home', 'classes', 'programs', 'lessons',
            'learn', 'explore', 'imagine', 'create', 'australia', 
            'favourite', 'holiday', 'camp', '404', 'not found',
            'page not found', 'error', '2 weeks', '2 week'
        ]
        
        if any(indicator in name_lower for indicator in invalid_indicators):
            return False
        
        # Check length
        if len(name.strip()) > 100:
            return False
            
        return True

    def clean_business_name(self, name, url):
        """Clean up business name with enhanced rules"""
        if not name:
            return ""
        
        original_name = name.strip()
        name_lower = original_name.lower()
        
        # Fix specific known issues based on URL
        url_lower = url.lower()
        if 'samuraikarate' in url_lower and ('2 weeks' in name_lower or name_lower == '2 weeks'):
            return 'Samurai Karate Sunshine'
        elif 'pointcookdance' in url_lower:
            return 'Point Cook Dance Centre'
        elif 'brooksschoolofdance' in url_lower and 'brooksschoolofdan' in name_lower:
            return 'Brooks School of Dance'
        elif 'wildatartkids' in url_lower:
            return 'Wild at Art KIDS'
        elif 'bkgymswim' in url_lower:
            return "BK's Gymnastics Caroline Springs"
        elif 'gymnastics-unlimited' in url_lower:
            return 'Gymnastics Unlimited Australia'
        elif 'martialjourney' in url_lower:
            return 'Martial Journey Academy'
        elif 'codecamp' in url_lower:
            return 'Code Camp'
        
        # Remove common problematic patterns
        name = re.sub(r'^[|\-–\s]*(2\s+WEEKS?|home|classes|melbourne|australia).*$', '', original_name, flags=re.IGNORECASE)
        name = re.sub(r'\s*[|\-–]\s*(home|classes|programs|lessons).*$', '', name, flags=re.IGNORECASE)
        name = re.sub(r'\s+', ' ', name)
        
        cleaned = name.strip()
        
        # If we ended up with nothing after cleaning, use domain-based name
        if not cleaned or len(cleaned) < 3:
            parsed_url = urlparse(url)
            domain_name = parsed_url.netloc.replace('www.', '').split('.')[0]
            return domain_name.replace('-', ' ').title()
        
        return cleaned

    async def extract_phone_flexible(self, page):
        """Extract phone using flexible strategies for different sites"""
        try:
            # Strategy 1: Look for tel: links
            try:
                phone_elements = await page.query_selector_all('a[href^="tel:"]')
                for element in phone_elements:
                    phone_href = await element.get_attribute('href')
                    if phone_href:
                        phone = phone_href.replace('tel:', '').strip()
                        if self.is_valid_au_phone(phone):
                            return self.format_phone_number(phone)
            except:
                pass
            
            # Strategy 2: Look in common contact selectors
            contact_selectors = [
                '.contact-phone', '.phone', '.contact-number',
                '.contact-info', '.contact-details', '.footer-contact',
                '[class*="contact"]', '[class*="phone"]',
                '.footer', '.header', '.call-now'
            ]
            
            for selector in contact_selectors:
                try:
                    elements = await page.query_selector_all(selector)
                    for elem in elements:
                        text = await elem.inner_text()
                        phone = self.extract_phone_from_text(text)
                        if phone:
                            return phone
                except:
                    continue
            
            # Strategy 3: Search entire page text
            try:
                page_text = await page.evaluate('() => document.body.innerText')
                phone = self.extract_phone_from_text(page_text)
                if phone:
                    return phone
            except:
                pass
            
            # Strategy 4: Look for phone numbers in data attributes
            try:
                elements_with_phone = await page.query_selector_all('[data-phone], [data-number], [data-tel]')
                for elem in elements_with_phone:
                    phone_attr = await elem.get_attribute('data-phone') or await elem.get_attribute('data-number') or await elem.get_attribute('data-tel')
                    if phone_attr and self.is_valid_au_phone(phone_attr):
                        return self.format_phone_number(phone_attr)
            except:
                pass
            
            # Strategy 5: Look for hidden elements with phone info
            try:
                hidden_elements = await page.query_selector_all('[style*="display: none"], [style*="visibility: hidden"], .hidden, .sr-only')
                for elem in hidden_elements:
                    text = await elem.inner_text()
                    phone = self.extract_phone_from_text(text)
                    if phone:
                        return phone
            except:
                pass
            
            return ""

        except Exception as e:
            self.logger.error(f"Error extracting phone: {str(e)}")
            return ""

    def extract_phone_from_text(self, text):
        """Extract Australian phone number from text"""
        if not text:
            return ""
        
        # Australian phone patterns
        patterns = [
            r'\(0[2-8]\)\s?\d{4}\s?\d{4}',          # (03) 8358 4361
            r'0[2-8]\s?\d{4}\s?\d{4}',             # 03 8358 4361
            r'\+61\s?[2-8]\s?\d{4}\s?\d{4}',       # +61 3 8358 4361
            r'0\d{3}\s?\d{3}\s?\d{3}',             # 0411 654 621
            r'1300\s?\d{3}\s?\d{3}',               # 1300 123 456
            r'0\d{2}\s?\d{3}\s?\d{3}',             # 041 500 886
            r'\+61\d{9}',                          # +61434726020
            r'0\d{9}',                             # 0416679911
            r'0\d{3}\d{3}\d{3}'                    # 0411654621
        ]
        
        for pattern in patterns:
            matches = re.findall(pattern, text)
            for match in matches:
                if self.is_valid_au_phone(match):
                    return self.format_phone_number(match)
        
        return ""

    def format_phone_number(self, phone):
        """Format phone number consistently"""
        if not phone:
            return ""
        
        # Clean the phone number
        cleaned = re.sub(r'[^\d+]', '', phone)
        
        # Format based on pattern
        if cleaned.startswith('0') and len(cleaned) == 10:
            return f"({cleaned[0:2]}) {cleaned[2:6]} {cleaned[6:10]}"
        elif cleaned.startswith('+61') and len(cleaned) == 11:
            return f"({cleaned[3:5]}) {cleaned[5:9]} {cleaned[9:11]}"
        elif cleaned.startswith('04') and len(cleaned) == 10:
            return f"{cleaned[0:4]} {cleaned[4:7]} {cleaned[7:10]}"
        else:
            return phone.strip()

    def is_valid_au_phone(self, phone):
        """Check if phone looks like valid Australian number"""
        if not phone:
            return False
        
        # Remove all non-digits except +
        cleaned = re.sub(r'[^\d+]', '', phone)
        
        # Check patterns
        patterns = [
            r'^\+61[2-8]\d{8}$',     # +61X XXXXXXXX
            r'^0[2-8]\d{8}$',        # 0X XXXXXXXX  
            r'^0\d{9}$',             # 10 digits
            r'^1300\d{6}$',          # 1300 numbers
            r'^\+61\d{9}$'           # +61434726020
        ]
        
        return any(re.match(pattern, cleaned) for pattern in patterns)

    async def extract_email_flexible(self, page):
        """Extract email using flexible strategies"""
        try:
            # Strategy 1: Look for mailto: links
            try:
                email_elements = await page.query_selector_all('a[href^="mailto:"]')
                for element in email_elements:
                    email_href = await element.get_attribute('href')
                    if email_href:
                        email = email_href.replace('mailto:', '').strip()
                        email = self.clean_email_address(email)
                        if self.is_valid_email(email):
                            return email.lower()
            except:
                pass
            
            # Strategy 2: Search page content
            try:
                content = await page.content()
                emails = re.findall(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', content)
                for email in emails:
                    email = self.clean_email_address(email)
                    if self.is_valid_email(email):
                        return email.lower()
            except:
                pass
            
            # Strategy 3: Look for email patterns in scripts
            try:
                scripts = await page.query_selector_all('script:not([src])')
                for script in scripts:
                    script_content = await script.inner_text()
                    # Look for email patterns that might be obfuscated
                    email_patterns = [
                        r'[\w\.-]+@[\w\.-]+\.\w+',
                        r'[\w\.-]+\[at\][\w\.-]+\.\w+',
                        r'[\w\.-]+\s*\(\s*at\s*\)\s*[\w\.-]+\.\w+'
                    ]
                    for pattern in email_patterns:
                        emails = re.findall(pattern, script_content, re.IGNORECASE)
                        for found_email in emails:
                            # Clean up obfuscated emails
                            clean_email = found_email.replace('[at]', '@').replace('(at)', '@').replace(' at ', '@')
                            if self.is_valid_email(clean_email):
                                return clean_email.lower()
            except:
                pass
            
            return ""
            
        except Exception as e:
            self.logger.error(f"Error extracting email: {str(e)}")
            return ""

    def clean_email_address(self, email):
        """Clean email address by removing query parameters and other noise"""
        if not email:
            return ""
        
        # Remove query parameters and fragments
        email = email.split('?')[0].split('&')[0].split('#')[0].strip()
        
        # Remove common tracking parameters
        email = re.sub(r'\?subject=.*$', '', email)
        email = re.sub(r'\?utm_.*$', '', email)
        
        return email

    def is_valid_email(self, email):
        """Check if email is valid"""
        if not email:
            return False
        
        email = self.clean_email_address(email)
        email_lower = email.lower()
        
        # Filter out common false positives
        invalid_patterns = [
            '.png', '.jpg', '.gif', '.pdf', '@example.', '@test.', '@domain.',
            '@placeholder.', '@sample.', 'noreply@', 'no-reply@', '.webp',
            '.svg', '.ico', '.css', '.js', '.woff', '.ttf'
        ]
        
        return (not any(pattern in email_lower for pattern in invalid_patterns) 
                and len(email_lower) > 5 
                and '@' in email_lower 
                and '.' in email_lower
                and ' ' not in email_lower
                and '..' not in email_lower)

    async def extract_addresses_improved(self, page, provider_url):
        """Improved address extraction with multiple strategies"""
        address_entries = []
        
        try:
            # Strategy 1: Look for address in common selectors
            address_selectors = [
                '[class*="address"]',
                '[class*="location"]',
                '[class*="contact"]',
                'address',
                '.footer',
                '.contact-info',
                '.business-info',
                '.location-details'
            ]
            
            for selector in address_selectors:
                try:
                    elements = await page.query_selector_all(selector)
                    for elem in elements:
                        text = await elem.inner_text()
                        if text and self.contains_address_indicators(text):
                            address_data = self.parse_address_text(text)
                            if address_data:
                                address_entries.append(address_data)
                                break
                    if address_entries:
                        break
                except:
                    continue
            
            # Strategy 2: Search entire page for address patterns
            if not address_entries:
                try:
                    page_text = await page.evaluate('() => document.body.innerText')
                    address_data = self.extract_address_from_page_text(page_text, provider_url)
                    if address_data:
                        address_entries.append(address_data)
                except:
                    pass
            
            # Strategy 3: Extract suburb from URL or page content
            if not address_entries:
                suburb = self.extract_suburb_from_context(provider_url, await page.content())
                if suburb:
                    address_entries.append({
                        'full_address': f'{suburb}, VIC',
                        'street_address': '',
                        'suburb': suburb,
                        'postcode': self.get_postcode_for_suburb(suburb),
                        'state': 'VIC'
                    })
        
        except Exception as e:
            self.logger.error(f"Error extracting addresses: {str(e)}")
        
        # Clean up address data
        cleaned_entries = []
        for addr in address_entries:
            cleaned_addr = self.clean_address_data(addr, provider_url)
            if cleaned_addr:
                cleaned_entries.append(cleaned_addr)
        
        # Ensure we always return at least an empty address entry
        if not cleaned_entries:
            cleaned_entries.append({
                'full_address': '',
                'street_address': '',
                'suburb': '',
                'postcode': '',
                'state': 'VIC'
            })
        
        return cleaned_entries

    def clean_address_data(self, address, provider_url):
        """Clean and validate address data"""
        if not address:
            return None
        
        # Clean suburb field
        suburb = address.get('suburb', '').strip()
        if suburb in ['s', 'a', '']:
            # Try to extract suburb from full address or URL
            full_addr = address.get('full_address', '')
            if full_addr:
                suburb_match = re.search(r'([A-Za-z\s]+)\s+VIC\s+\d{4}', full_addr)
                if suburb_match:
                    suburb = suburb_match.group(1).strip()
            
            if not suburb or suburb in ['s', 'a']:
                suburb = self.extract_suburb_from_context(provider_url, "")
        
        # Clean full address
        full_address = address.get('full_address', '')
        if full_address:
            # Remove extra whitespace and newlines
            full_address = re.sub(r'\s+', ' ', full_address)
            full_address = re.sub(r'\n', ', ', full_address)
            full_address = full_address.strip()
        
        return {
            'full_address': full_address,
            'street_address': address.get('street_address', '').strip(),
            'suburb': suburb,
            'postcode': address.get('postcode', '').strip(),
            'state': 'VIC'
        }

    def contains_address_indicators(self, text):
        """Check if text contains address indicators"""
        if not text:
            return False
        
        text_upper = text.upper()
        address_indicators = [
            'VIC', 'VICTORIA', 'STREET', 'ST', 'ROAD', 'RD', 'AVENUE', 'AVE',
            'DRIVE', 'DR', 'LANE', 'LN', 'COURT', 'CT', 'PLACE', 'PL',
            'BOULEVARD', 'BLVD', 'CRESCENT', 'CRES', 'WAY'
        ]
        
        return (any(indicator in text_upper for indicator in address_indicators) 
                and re.search(r'\b\d{4}\b', text))

    def parse_address_text(self, text):
        """Parse address text into structured components"""
        if not text:
            return None
        
        # Clean the text first
        text = re.sub(r'\s+', ' ', text)
        text = re.sub(r'\n', ' ', text)
        
        # Look for suburb and postcode pattern
        suburb_postcode_match = re.search(r'([A-Za-z\s]+)\s+VIC\s+(\d{4})', text, re.IGNORECASE)
        if suburb_postcode_match:
            suburb = suburb_postcode_match.group(1).strip()
            postcode = suburb_postcode_match.group(2).strip()
            
            # Extract street address (text before suburb)
            street_address = text[:suburb_postcode_match.start()].strip()
            if street_address.endswith(','):
                street_address = street_address[:-1].strip()
            
            # Clean up suburb name
            suburb = self.clean_suburb_name(suburb)
            
            return {
                'full_address': f"{street_address}, {suburb} VIC {postcode}" if street_address else f"{suburb} VIC {postcode}",
                'street_address': street_address,
                'suburb': suburb,
                'postcode': postcode,
                'state': 'VIC'
            }
        
        return None

    def extract_address_from_page_text(self, text, url):
        """Extract address from page text using patterns"""
        if not text:
            return None
        
        # Clean the text
        text = re.sub(r'\s+', ' ', text)
        
        # Look for common address patterns
        patterns = [
            r'(\d+[\w\s,]+)([A-Za-z\s]+)\s+VIC\s+(\d{4})',
            r'([A-Za-z\s]+)\s+VIC\s+(\d{4})'
        ]
        
        for pattern in patterns:
            matches = re.findall(pattern, text, re.IGNORECASE)
            for match in matches:
                if len(match) == 3:  # Full address with street
                    street, suburb, postcode = match
                    suburb = self.clean_suburb_name(suburb)
                    return {
                        'full_address': f"{street.strip()}, {suburb} VIC {postcode}",
                        'street_address': street.strip(),
                        'suburb': suburb,
                        'postcode': postcode,
                        'state': 'VIC'
                    }
                elif len(match) == 2:  # Just suburb and postcode
                    suburb, postcode = match
                    suburb = self.clean_suburb_name(suburb)
                    return {
                        'full_address': f"{suburb} VIC {postcode}",
                        'street_address': '',
                        'suburb': suburb,
                        'postcode': postcode,
                        'state': 'VIC'
                    }
        
        return None

    def clean_suburb_name(self, suburb):
        """Clean suburb name"""
        if not suburb:
            return ""
        
        suburb = suburb.strip()
        # Remove trailing commas and other punctuation
        suburb = re.sub(r'[,\-–]\s*$', '', suburb)
        # Fix common issues
        if suburb == 's':
            return 'Taylors Lake'
        elif suburb == 'a':
            return 'Point Cook'
        
        return suburb

    def extract_suburb_from_context(self, url, page_content):
        """Extract suburb from URL or page content with better accuracy"""
        url_lower = url.lower()
        content_lower = page_content.lower() if page_content else ""
        
        # Enhanced suburb mappings
        suburb_mappings = {
            'caroline-springs': 'Caroline Springs',
            'point-cook': 'Point Cook', 
            'pointcook': 'Point Cook',
            'sunshine': 'Sunshine',
            'truganina': 'Truganina',
            'taylors-lake': 'Taylors Lake',
            'taylorslake': 'Taylors Lake',
            'kealba': 'Kealba',
            'werribee': 'Werribee'
        }
        
        for url_pattern, suburb in suburb_mappings.items():
            if url_pattern in url_lower:
                return suburb
        
        # Enhanced content checking with word boundaries
        western_suburbs = [
            'caroline springs', 'point cook', 'truganina', 'sunshine',
            'taylors lake', 'werribee', 'hoppers crossing', 'tarneit',
            'kealba', 'brooklyn'
        ]
        
        for suburb in western_suburbs:
            if re.search(r'\b' + re.escape(suburb) + r'\b', content_lower):
                return suburb.title()
        
        return ""

    def get_postcode_for_suburb(self, suburb):
        """Get approximate postcode for known Western Melbourne suburbs"""
        postcode_mapping = {
            'Caroline Springs': '3023',
            'Point Cook': '3030',
            'Sunshine': '3020',
            'Truganina': '3029',
            'Werribee': '3030',
            'Hoppers Crossing': '3029',
            'Tarneit': '3029',
            'Taylors Lake': '3038',
            'Kealba': '3021'
        }
        return postcode_mapping.get(suburb, '')

    async def errback_handler(self, failure):
        """Handle request errors"""
        page = failure.request.meta.get("playwright_page")
        if page:
            try:
                await page.close()
            except:
                pass
            
        self.logger.error(f"Request failed: {failure.request.url}")