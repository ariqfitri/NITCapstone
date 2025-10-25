from flask import Flask, render_template, url_for, request, redirect
from flask_login import LoginManager, login_user, logout_user, login_required
from flask_bcrypt import Bcrypt
from sqlalchemy import inspect

from db import db
import models

app = Flask(__name__)

# database config
app.config['SQLALCHEMY_DATABASE_URI'] = 'mysql+pymysql://root:@localhost/kidssmart'
app.config['SECRET_KEY'] = 'rat'

db.init_app(app)
bcrypt = Bcrypt(app)
login_manager = LoginManager(app)


@app.route('/', methods=['GET', 'POST'])
def index():
    query = request.args.get('q')  # get ?q=... from URL
    if query:
        activities = models.Activities.query.filter(models.Activities.title.like(f"%{query}%")).all()
    else:
        activities = models.Activities.query.all()
    return render_template('index.html', activities=activities, query=query)


@app.route('/dashboard')
@login_required
def dashboard():
    return render_template('dashboard.html')


@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('index'))


@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'GET':
        return render_template('register.html')
    elif request.method == 'POST':
        register_username = request.form['register_username']
        register_password = request.form['register_password']

        hashed_password = bcrypt.generate_password_hash(register_password)
        username_exists = models.Users.query.filter_by(username=register_username).first()

        if not username_exists:
            user_row = models.Users(username=register_username, password=hashed_password)
            db.session.add(user_row)
            db.session.commit()

        return redirect(url_for('login'))
    return "Invalid Request Method", 400


@login_manager.user_loader
def load_user(user_id):
    return db.session.get(models.Users, int(user_id))


@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'GET':
        return render_template('login.html')
    elif request.method == 'POST':
        login_username = request.form['login_username']
        login_password = request.form['login_password']

        correct_user = models.Users.query.filter_by(username=login_username).first()
        if correct_user and bcrypt.check_password_hash(correct_user.password, login_password):
            login_user(correct_user)
            return redirect(url_for('dashboard'))
        else:
            return redirect(url_for('login'))
    return "Invalid Request Method", 400


def table_exist():
    engine = db.engine
    inspector = inspect(engine)
    tables = inspector.get_table_names()
    return len(tables) > 0


with app.app_context():
        db.create_all()


if __name__ == '__main__':
    app.run(debug=True)


