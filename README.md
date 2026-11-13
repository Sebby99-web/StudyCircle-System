# StudyCircle

**A Web-Based Study Group Finder and Session Scheduler**

StudyCircle is a campus-focused web application that
helps students discover, host, and join study sessions.
Administrators manage academic subjects, review
study sessions, and monitor platform activity.

## Technology Stack

- Frontend: HTML5, CSS3, Bootstrap 5
- JavaScript: ES6+, Fetch API
- Backend: PHP 8 with PDO
- Database: MySQL / MariaDB
- Analytics: Chart.js
- Browser storage: localStorage
- Development environment: XAMPP

## User Roles

### Student

- Register and log in
- Browse upcoming study sessions
- Search and filter sessions
- Host new study sessions
- Edit or cancel hosted sessions
- Join and leave sessions
- View hosted and joined sessions
- Edit profile details and password
- Save unfinished hosting forms
- Download calendar events

### Administrator

- Access protected admin dashboard
- Manage academic subjects
- Review and moderate study sessions
- View users and manage roles
- View study-session analytics
- Monitor current membership statistics

## Main Features

### Authentication

Passwords are stored using PHP password_hash().
Authentication uses PHP sessions, with role-based
access checks and CSRF protection for state-changing
forms.

### Study Sessions

Each session contains:

- Subject
- Topic
- Venue
- Date
- Start and end time
- Capacity
- Status: open, full, cancelled

Membership operations use database transactions
and a unique session/member constraint.

### Live Search

The Browse page supports filtering by:

- Topic or host
- Subject
- Date
- Venue

Search results update using JavaScript Fetch API
without a full page reload.

### Modern Features

- Chart.js administrator analytics
- Persistent dark mode
- Saved search filters using localStorage
- Unfinished session drafts using localStorage
- Optional iCalendar (.ics) export

## Database

Database name:

studycircle_system

Core tables:

1. users
2. subjects
3. study_sessions
4. session_members

SQL schema:

database/studycircle_system.sql

## Local Installation

1. Install XAMPP with PHP and MySQL.
2. Start Apache and MySQL.
3. Place this folder inside:
   C:\xampp\htdocs\StudyCircle-System
4. Open http://localhost/phpmyadmin.
5. Import database/studycircle_system.sql.
6. Copy config/database.example.php to:
   config/database.php
7. Adjust database credentials if needed.
8. Open:
   http://localhost/StudyCircle-System/

## Initial Administrator Setup

Public registration creates Student accounts only.

Register a trusted account through the website,
then promote it once using phpMyAdmin:

UPDATE studycircle_system.users
SET role = 'admin'
WHERE email = 'registered-admin@example.com';

Replace the sample email with the actual
registered account.

Never allow users to select the Admin role
during public registration.

## Project Checks

Use PHP CLI for the read-only preflight checker:

C:\xampp\php\php.exe
C:\xampp\htdocs\StudyCircle-System\tools\preflight.php

Use the full command on one terminal line.

This checks required files, database tables,
membership uniqueness, and foreign keys.

The preflight checker does not replace
browser-based functional testing.

## Security Notes

- Do not upload real database passwords to GitHub.
- Keep config/database.php private.
- Deploy using HTTPS.
- Configure production database credentials securely.
- Restrict access to internal folders.
- Disable detailed PHP error output in production.
- Use a least-privilege database account in production.

## Deployment

Deployment is a separate step after the complete
system passes local integration tests.

The folder name and session cookie path must
match the public hosting configuration.

Do not deploy using a blank MySQL root password.

## Project Status

Phase 1: Authentication and database foundation
Phase 2: Session management
Phase 3: Live search and filtering
Phase 4: Administrator management and charts
Phase 5: Profile, drafts, and calendar export
Phase 6: Integration preparation and testing

Implementation requires final integration testing
before release.
