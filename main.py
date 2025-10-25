import os
from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from flask_sqlalchemy import SQLAlchemy
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime

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

# Secondary database (app data - activities, books)
app.config['SQLALCHEMY_BINDS'] = {
    'app_data': f'mysql+pymysql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}/{DB_NAME_APP}'
}

app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['SQLALCHEMY_ECHO'] = True  # Log SQL queries in development

db = SQLAlchemy(app)

# ============================================================================
# USER DATABASE MODELS (kidssmart_users)
# ============================================================================

class User(db.Model):
    __tablename__ = 'users'
    
    user_id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(255), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    is_verified = db.Column(db.Boolean, default=False)
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    last_login = db.Column(db.DateTime, nullable=True)
    
    # Relationships
    profile = db.relationship('UserProfile', backref='user', uselist=False, cascade='all, delete-orphan')
    favorites = db.relationship('UserFavorite', backref='user', cascade='all, delete-orphan')
    reviews = db.relationship('UserReview', backref='user', cascade='all, delete-orphan')
    
    def set_password(self, password):
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        return check_password_hash(self.password_hash, password)


class UserProfile(db.Model):
    __tablename__ = 'user_profiles'
    
    profile_id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.user_id'), nullable=False)
    first_name = db.Column(db.String(100))
    last_name = db.Column(db.String(100))
    suburb = db.Column(db.String(100))
    postcode = db.Column(db.String(10))
    phone = db.Column(db.String(20))
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)


class UserFavorite(db.Model):
    __tablename__ = 'user_favorites'
    
    favorite_id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.user_id'), nullable=False)
    item_type = db.Column(db.Enum('activity', 'book'), nullable=False)
    item_id = db.Column(db.Integer, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class UserReview(db.Model):
    __tablename__ = 'user_reviews'
    
    review_id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.user_id'), nullable=False)
    item_type = db.Column(db.Enum('activity', 'book'), nullable=False)
    item_id = db.Column(db.Integer, nullable=False)
    rating = db.Column(db.Integer, nullable=False)  # 1-5
    review_text = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)


# ============================================================================
# APP DATA MODELS (kidssmart_app) - from scrapers
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
    source_url = db.Column(db.String(500))
    scraped_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    is_approved = db.Column(db.Boolean, default=False)


class Book(db.Model):
    __bind_key__ = 'app_data'
    __tablename__ = 'books'
    
    book_id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(255), nullable=False)
    author = db.Column(db.String(255))
    isbn = db.Column(db.String(20))
    description = db.Column(db.Text)
    age_range = db.Column(db.String(50))
    category = db.Column(db.String(100))
    cover_image_url = db.Column(db.String(500))
    source_url = db.Column(db.String(500))
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


# ============================================================================
# ROUTES
# ============================================================================

@app.route('/')
def index():
    """Homepage with activities list"""
    # Get all approved activities from app_data database
    activities = Activity.query.filter_by(is_approved=True).limit(20).all()
    return render_template('index.html', activities=activities)


@app.route('/activities')
def activities():
    """Activities listing page with filters"""
    category = request.args.get('category')
    suburb = request.args.get('suburb')
    
    query = Activity.query.filter_by(is_approved=True)
    
    if category:
        query = query.filter_by(category=category)
    if suburb:
        query = query.filter_by(suburb=suburb)
    
    activities = query.all()
    categories = db.session.execute(
        db.select(Category.category_name).bind_mapper(Category)
    ).scalars().all()
    
    return render_template('activities.html', activities=activities, categories=categories)


@app.route('/activity/<int:activity_id>')
def activity_detail(activity_id):
    """Individual activity details page"""
    activity = Activity.query.get_or_404(activity_id)
    
    # Get reviews for this activity (if user is logged in)
    reviews = []
    if 'user_id' in session:
        reviews = UserReview.query.filter_by(
            item_type='activity',
            item_id=activity_id
        ).all()
    
    return render_template('activity_detail.html', activity=activity, reviews=reviews)


@app.route('/books')
def books():
    """Books listing page"""
    books = Book.query.filter_by(is_approved=True).all()
    return render_template('books.html', books=books)


@app.route('/register', methods=['GET', 'POST'])
def register():
    """User registration"""
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        
        # Check if user exists
        if User.query.filter_by(email=email).first():
            flash('Email already registered', 'error')
            return redirect(url_for('register'))
        
        # Create new user
        user = User(email=email)
        user.set_password(password)
        db.session.add(user)
        db.session.commit()
        
        flash('Registration successful! Please log in.', 'success')
        return redirect(url_for('login'))
    
    return render_template('register.html')


@app.route('/login', methods=['GET', 'POST'])
def login():
    """User login"""
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        
        user = User.query.filter_by(email=email).first()
        
        if user and user.check_password(password):
            session['user_id'] = user.user_id
            session['email'] = user.email
            user.last_login = datetime.utcnow()
            db.session.commit()
            
            flash('Login successful!', 'success')
            return redirect(url_for('dashboard'))
        
        flash('Invalid email or password', 'error')
    
    return render_template('login.html')


@app.route('/logout')
def logout():
    """User logout"""
    session.clear()
    flash('You have been logged out', 'info')
    return redirect(url_for('index'))


@app.route('/dashboard')
def dashboard():
    """User dashboard"""
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    user = User.query.get(session['user_id'])
    favorites = UserFavorite.query.filter_by(user_id=user.user_id).all()
    
    return render_template('dashboard.html', user=user, favorites=favorites)


@app.route('/api/activities')
def api_activities():
    """API endpoint for activities (JSON)"""
    activities = Activity.query.filter_by(is_approved=True).all()
    return jsonify([{
        'id': a.activity_id,
        'title': a.title,
        'category': a.category,
        'suburb': a.suburb
    } for a in activities])


# ============================================================================
# DATABASE INITIALIZATION
# ============================================================================

with app.app_context():
    # Create all tables in both databases
    db.create_all()
    print("Database tables created successfully!")


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
