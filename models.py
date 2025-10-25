from flask_login import UserMixin
from db import db

class Users(UserMixin, db.Model):
    __tablename__ = "users"
    user_id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(50), unique=True, nullable=False)
    password = db.Column(db.String(200), nullable=False)

    def get_id(self):
        return str(self.user_id)

class Activities(db.Model):
    __tablename__ = 'activities'

    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(255))
    address = db.Column(db.String(255))
    suburb = db.Column(db.String(255))
    postcode = db.Column(db.String(10))
    activity_type = db.Column(db.String(255))

    def __repr__(self):
        return f"<Activity {self.title}>"