import os
from flask_sqlalchemy import SQLAlchemy
from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime
from db import db  # Import the shared db instance

app = Flask(__name__)
app.secret_key = os.getenv('SECRET_KEY', 'dev-secret-key-change-in-production')

# Database Configuration
DB_HOST = os.getenv('DB_HOST', 'database')
DB_USER = os.getenv('DB_USER', 'app_user')
DB_PASSWORD = os.getenv('DB_PASSWORD', 'AppPass123!')
DB_NAME_USERS = os.getenv('DB_NAME_USERS', 'kidssmart_users')
DB_NAME_APP = os.getenv('DB_NAME_APP', 'kidssmart_app')

# Primary database (users)
app.config['SQLALCHEMY_DATABASE_URI'] = f'mysql+pymysql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}/{DB_NAME_USERS}'

# Secondary database (app data - activities)
app.config['SQLALCHEMY_BINDS'] = {
    'app_data': f'mysql+pymysql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}/{DB_NAME_APP}'
}

app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['SQLALCHEMY_ECHO'] = True

# Initialize the shared db instance with the app
db.init_app(app)

# ============================================================================
# USER DATABASE MODELS (kidssmart_users)
# ============================================================================

class User(db.Model):
    __tablename__ = 'users'
    
    user_id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(255), unique=True, nullable=False)
    username = db.Column(db.String(255), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    first_name = db.Column(db.String(100))
    last_name = db.Column(db.String(100))
    suburb = db.Column(db.String(100))
    postcode = db.Column(db.String(10))
    child_age_range = db.Column(db.String(50))
    preferences = db.Column(db.JSON)  # ✅ FIXED: Added missing field
    is_verified = db.Column(db.Boolean, default=False)
    is_active = db.Column(db.Boolean, default=True)
    is_admin = db.Column(db.Boolean, default=False)
    verification_token = db.Column(db.String(100))  # ✅ FIXED: Added missing field
    reset_token = db.Column(db.String(100))  # ✅ FIXED: Added missing field
    reset_expires = db.Column(db.DateTime)  # ✅ FIXED: Added missing field
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)  # ✅ FIXED: Added missing field
    last_login = db.Column(db.DateTime, nullable=True)
    
    # ✅ FIXED: Updated relationships to match PHP schema
    favourites = db.relationship('UserFavorite', backref='user', cascade='all, delete-orphan')
    reviews = db.relationship('UserReview', backref='user', cascade='all, delete-orphan')
    sessions = db.relationship('UserSession', backref='user', cascade='all, delete-orphan')  # ✅ NEW
    
    def set_password(self, password):
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        return check_password_hash(self.password_hash, password)


class UserFavorite(db.Model):
    __tablename__ = 'favourites'  # ✅ FIXED: Match PHP table name
    
    favourite_id = db.Column(db.Integer, primary_key=True)  # ✅ FIXED: Match PHP field name
    user_id = db.Column(db.Integer, db.ForeignKey('users.user_id'), nullable=False)
    activity_id = db.Column(db.Integer, nullable=False)  # Links to activities table in app_data DB
    activity_title = db.Column(db.String(255), nullable=False)  # ✅ FIXED: Added missing field
    activity_url = db.Column(db.String(500), nullable=False)  # ✅ FIXED: Added missing field
    activity_image = db.Column(db.String(500))  # ✅ FIXED: Added missing field
    activity_age_range = db.Column(db.String(50))  # ✅ FIXED: Added missing field
    activity_category = db.Column(db.String(100))  # ✅ FIXED: Added missing field
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class UserReview(db.Model):
    __tablename__ = 'reviews'  # ✅ FIXED: Match PHP table name
    
    review_id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.user_id'), nullable=False)
    activity_id = db.Column(db.Integer, nullable=False)  # Links to activities table in app_data DB
    rating = db.Column(db.Integer, nullable=False)  # 1-5
    review_text = db.Column(db.Text)
    is_approved = db.Column(db.Boolean, default=True)  # ✅ FIXED: Added missing field
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)


# ✅ NEW: Add UserSession model to match PHP schema
class UserSession(db.Model):
    __tablename__ = 'sessions'
    
    session_id = db.Column(db.String(128), primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.user_id'), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    expires_at = db.Column(db.DateTime, nullable=False)


# ============================================================================
# APP DATA MODELS (kidssmart_app)
# ============================================================================

class Activity(db.Model):
    __bind_key__ = 'app_data'
    __tablename__ = 'activities'
    
    activity_id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(255), nullable=False)
    description = db.Column(db.Text)
    category = db.Column(db.String(100))
    suburb = db.Column(db.String(100))
    postcode = db.Column(db.String(10))
    address = db.Column(db.Text)
    phone = db.Column(db.String(20))
    email = db.Column(db.String(255))
    website = db.Column(db.String(500))
    age_range = db.Column(db.String(50))
    cost = db.Column(db.String(100))
    schedule = db.Column(db.Text)
    image_url = db.Column(db.String(500))
    source_url = db.Column(db.String(500), unique=True)
    source_name = db.Column(db.String(100))  # 'activeactivities', 'kidsbook', etc.
    scraped_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    is_approved = db.Column(db.Boolean, default=False)


class Category(db.Model):
    __bind_key__ = 'app_data'
    __tablename__ = 'categories'
    
    category_id = db.Column(db.Integer, primary_key=True)
    category_name = db.Column(db.String(100), unique=True, nullable=False)
    description = db.Column(db.Text)


class Location(db.Model):
    __bind_key__ = 'app_data'
    __tablename__ = 'locations'
    
    location_id = db.Column(db.Integer, primary_key=True)
    suburb = db.Column(db.String(100), nullable=False)
    postcode = db.Column(db.String(10), nullable=False)
    state = db.Column(db.String(50))


# ✅ NEW: Add ScrapingLog model to match schema
class ScrapingLog(db.Model):
    __bind_key__ = 'app_data'
    __tablename__ = 'scraping_logs'
    
    log_id = db.Column(db.Integer, primary_key=True)
    scraper_name = db.Column(db.String(100), nullable=False)
    status = db.Column(db.Enum('started', 'completed', 'failed'), nullable=False)
    message = db.Column(db.Text)
    run_at = db.Column(db.DateTime, default=datetime.utcnow)


# ============================================================================
# API ROUTES
# ============================================================================

@app.route('/api/status')
def api_status():
    """API endpoint to check Flask app status"""
    return jsonify({
        'status': 'running',
        'message': 'Flask API is operational',
        'timestamp': datetime.utcnow().isoformat()
    })


@app.route('/api/activities')
def api_activities():
    """API endpoint for activities - used by PHP frontend"""
    try:
        page = request.args.get('page', 1, type=int)
        limit = request.args.get('limit', 12, type=int)
        category = request.args.get('category', '')
        suburb = request.args.get('suburb', '')
        search = request.args.get('search', '')
        
        query = Activity.query.filter_by(is_approved=True)
        
        if category:
            query = query.filter_by(category=category)
        if suburb:
            query = query.filter_by(suburb=suburb)
        if search:
            query = query.filter(
                db.or_(
                    Activity.title.contains(search),
                    Activity.description.contains(search)
                )
            )
        
        total = query.count()
        activities = query.paginate(
            page=page, 
            per_page=limit, 
            error_out=False
        ).items
        
        return jsonify({
            'activities': [{
                'activity_id': a.activity_id,
                'title': a.title,
                'description': a.description,
                'category': a.category,
                'suburb': a.suburb,
                'postcode': a.postcode,
                'image_url': a.image_url,
                'age_range': a.age_range,
                'cost': a.cost
            } for a in activities],
            'total': total,
            'page': page,
            'limit': limit
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/activity/<int:activity_id>')
def api_activity_detail(activity_id):
    """API endpoint for single activity details"""
    try:
        activity = Activity.query.filter_by(
            activity_id=activity_id, 
            is_approved=True
        ).first_or_404()
        
        return jsonify({
            'activity_id': activity.activity_id,
            'title': activity.title,
            'description': activity.description,
            'category': activity.category,
            'suburb': activity.suburb,
            'postcode': activity.postcode,
            'address': activity.address,
            'phone': activity.phone,
            'email': activity.email,
            'website': activity.website,
            'age_range': activity.age_range,
            'cost': activity.cost,
            'schedule': activity.schedule,
            'image_url': activity.image_url,
            'source_name': activity.source_name,
            'scraped_at': activity.scraped_at.isoformat() if activity.scraped_at else None
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500


# ✅ REMOVED: All user-facing routes that conflict with PHP
# The PHP application handles:
# - / (homepage)
# - /activities (activity listing)
# - /activity/<id> (activity details)
# - User registration, login, profiles
# - Favourites management

# ✅ KEEP: Flask only handles API endpoints and admin functions


if __name__ == '__main__':
    with app.app_context():
        db.create_all()
    app.run(host='0.0.0.0', port=5000, debug=True)