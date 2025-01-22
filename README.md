Detailed Explanation of What the Plugin Does
Plugin Name: Listeo Listing User Assign
Description: This plugin provides a custom administrative interface to create or associate WordPress user accounts based on “listing” posts.

Key Features
Custom Admin Page:

The plugin adds a new page in the WordPress admin menu labeled “Listing User Assign.”
Only users with the manage_options capability (typically admins) can see and use this page.
Owner Role Creation:

On activation, the plugin automatically checks if a user role named “owner” exists. If not, it creates one.
This “owner” role can later be given specific capabilities as needed.
Filtering Listings:

The admin page shows listings of the custom post type listing.
You can choose to hide or show listings owned by an administrator. By default, it hides listings whose post author is an admin.
A simple form at the top of the page allows you to toggle between “Show all listings” and “Hide admin-owned listings.”
Pagination:

The listing table on this admin page is paginated, displaying 30 listings at a time.
You can navigate through pages with previous and next links.
Bulk or Single User Creation and Assignment:

Each listing shows the current post author, the title, the _email meta field, and an action link.
You can select multiple listings and click “Bulk Create Users” to process them all at once.
Or you can use the “Create User & Assign” button next to each listing to handle them individually.
How User Creation/Assignment Works:

The plugin reads the _email meta field from each listing.
If this email is empty, it skips creating a user for that listing.
If a user with the same email address already exists, the listing is reassigned to that existing user.
If no user exists for that email, the plugin creates a new one:
user_login = A sanitized version of the listing title.
user_email = The _email meta field from the listing.
role = owner.
A random password is generated, but no notification email is sent to the user.
After creation, the listing’s post_author is updated to point to the newly created (or matched) user.
No Emails Sent:

The plugin explicitly disables user notification emails by using send_user_notification => false in wp_insert_user().
Therefore, new users are created silently without any welcome or password emails.
Redirect After Submission:

When you initiate a bulk or single user creation action, the plugin runs and then redirects back to the admin page to avoid duplicate form submissions.
It preserves your chosen filter (show/hide admin-owned listings).
Edit and View Listing:

The listing title is linked to the regular WordPress edit screen for that post (so you can quickly edit it).
There is also a “View listing” link that opens the post permalink in a new browser tab for preview or front-end review.
Use Cases
Automatic Account Creation: If you have a large number of listings with unique email addresses, you can quickly bulk-create user accounts tied to those listings.
Assign Existing Users: If the user already exists by email, there’s no duplication; the listing simply gets reassigned.
Owner Role Management: Helps keep owners separate from subscribers, customers, or other roles. You can then give “owners” specific permissions in WordPress.
How to Install & Use
Installation:

Upload the plugin folder to /wp-content/plugins/.
Activate the plugin from “Plugins” in the admin area.
On activation, the plugin will create the “owner” role.
Usage:

Go to Listing User Assign in the WordPress Admin menu.
Choose whether to hide admin-owned listings or show all listings.
Select one or more listings using the checkboxes.
Click Bulk Create Users or use the Create User & Assign button for a single listing.
The plugin will create or find users based on each listing’s _email field and then assign those listings to the appropriate user.
Requirements:

A custom post type named listing.
Each listing should have _email meta data to link or create a user.
The user role “owner” is optional but helpful for organizing your site’s user structure.
