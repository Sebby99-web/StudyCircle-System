# StudyCircle — Full System Test Plan

Project: StudyCircle-System
Database: studycircle_system

## Test Accounts

Create three accounts for testing:

- Student A: Session host
- Student B: Session participant
- Administrator: Platform management

Use separate browsers or private windows when
testing actions involving multiple accounts.

## 1. Authentication

- [ ] Register a new student account
- [ ] Reject registration with an existing email
- [ ] Reject passwords shorter than 8 characters
- [ ] Reject mismatched passwords
- [ ] Log in with correct credentials
- [ ] Reject incorrect credentials
- [ ] Keep the student logged in during navigation
- [ ] Log out successfully
- [ ] Block unauthorized access to protected pages

## 2. Hosting Sessions

- [ ] Create a study session with valid details
- [ ] Confirm it appears in Explore Sessions
- [ ] Confirm it appears in My Sessions
- [ ] Reject a past start date or time
- [ ] Reject an end time before the start time
- [ ] Reject invalid capacity
- [ ] Reject an invalid subject
- [ ] Edit an upcoming hosted session
- [ ] Reject edits by another student
- [ ] Reject reducing capacity below joined members
- [ ] Cancel a hosted session
- [ ] Hide cancelled sessions from public browsing

## 3. Joining and Leaving

- [ ] Student B joins Student A's session
- [ ] Membership count increases
- [ ] Session appears in Student B's My Sessions
- [ ] Prevent duplicate joining
- [ ] Prevent hosts from joining their own sessions
- [ ] Fill a session to its capacity
- [ ] Automatically mark the session as Full
- [ ] Prevent additional students from joining
- [ ] Student B leaves the session
- [ ] Membership count decreases
- [ ] Automatically reopen an eligible Full session
- [ ] Prevent joining cancelled or started sessions

## 4. Concurrent Membership

- [ ] Create a session with one remaining seat
- [ ] Use two different student accounts
- [ ] Attempt to join simultaneously
- [ ] Confirm only one succeeds
- [ ] Confirm membership count never exceeds capacity

## 5. Live Search

- [ ] Search by topic
- [ ] Search by host name
- [ ] Filter by subject
- [ ] Filter by session date
- [ ] Filter by venue
- [ ] Combine multiple filters
- [ ] Clear all filters
- [ ] Confirm results update without page reload
- [ ] Confirm Join and Leave work after filtering
- [ ] Refresh and check saved filters
- [ ] Test normal search with JavaScript disabled

## 6. Administrator

- [ ] Student cannot access admin pages
- [ ] Administrator can access the dashboard
- [ ] Add a new subject
- [ ] Edit subject code and name
- [ ] Reject duplicate subject codes
- [ ] Delete an unused subject
- [ ] Prevent deletion of a referenced subject
- [ ] Review all study sessions
- [ ] Cancel inappropriate sessions
- [ ] View registered users
- [ ] Promote a student to administrator
- [ ] Demote another administrator when permitted
- [ ] Prevent an administrator changing their own role
- [ ] Verify both Chart.js charts load
- [ ] Verify statistics match database records

## 7. Profile and Modern Features

- [ ] Update full name
- [ ] Update email with current password
- [ ] Reject an existing email
- [ ] Change password successfully
- [ ] Reject incorrect current password
- [ ] Log in with the changed password
- [ ] Switch between light and dark mode
- [ ] Refresh and confirm theme persistence
- [ ] Start filling out a session form
- [ ] Navigate away and restore the draft
- [ ] Clear a saved draft
- [ ] Download a hosted session as .ics
- [ ] Download a joined session as .ics
- [ ] Reject calendar access by unrelated users
- [ ] Confirm the calendar has the correct time

## 8. UI and Deployment Readiness

- [ ] Test on a desktop browser
- [ ] Test on a mobile-sized screen
- [ ] Test the responsive navigation
- [ ] Check all links and buttons
- [ ] Check empty states
- [ ] Check error and success messages
- [ ] Check PHP logs for warnings or fatal errors
- [ ] Confirm internal directories are protected
- [ ] Verify no credentials are committed to Git
- [ ] Verify database backup and restore process
- [ ] Confirm deployed HTTPS and database connection

## Release Decision

Status: NOT YET VERIFIED

Only mark the system ready when all critical
authentication, authorization, database, and
session-management tests pass.

Document any failures and retest after fixing them.
