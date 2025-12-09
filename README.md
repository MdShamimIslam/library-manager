# Library Manager (WordPress Plugin)

Manage books in a custom database table via a secure REST API and a React-powered admin UI.

## Repository structure

- build/ — production JS/CSS and asset manifest used by WordPress admin
- includes/ — PHP classes for REST API and admin page
- src/ — React source (components, API utilities, styles)
- library-manager.php — main plugin bootstrap (activation, includes)

## Installation

1) Create/install the ZIP
- Zip the entire `library-manager` folder into `library-manager.zip`, or clone the repo and zip the folder. 
- In WordPress Admin → Plugins → Add New → Upload Plugin → select `library-manager.zip` → Install → Activate.

2) Activation
- On activation the table `{$wpdb->prefix}library_books` is created.
- The admin UI will appear at WordPress Admin → Library Manager.

## Building the React app

Prerequisites: Node.js and npm installed.

- From the plugin root: `npm install`
- Build to `build/`: `npm run build`
  - This uses `@wordpress/scripts` to bundle `src/index.js` into the `build/` directory.
  - Ensure `build/index.js`, `build/index.css`, and `build/index.asset.php` exist after bundling.

If you prefer a one-off production build, you can run: `npx wp-scripts build src/index.js --output-path=build` (optional).

## Admin UI

- Menu: WordPress Admin → Library Manager
- The admin renders a SPA inside a `<div id="lm-root"></div>` and communicates with the REST API using a localized nonce.

## REST API

Base: `/wp-json/library/v1`

1) GET `/books`
- Query params (optional): `status`, `author`, `year`, `page`, `per_page`
- Response: array of book objects

2) GET `/books/{id}`
- Response: single book object or 404

3) POST `/books`
- Requires capability: `edit_posts`
- Headers: `Content-Type: application/json`, `X-WP-Nonce: <nonce>`
- Body (JSON):
  - `title` (string, required)
  - `description` (string)
  - `author` (string)
  - `publication_year` (integer)
  - `status` (one of `available` | `borrowed` | `unavailable`)
- Response: created book (201) or validation error

4) PUT `/books/{id}`
- Requires capability: `edit_posts`
- Headers: `Content-Type: application/json`, `X-WP-Nonce: <nonce>`
- Body: any of the POST fields to update
- Response: updated book or error

5) DELETE `/books/{id}`
- Requires capability: `edit_posts`
- Headers: `X-WP-Nonce: <nonce>`
- Response: `null` on success, 404 if not found

Notes
- For mutating requests (POST/PUT/DELETE), include the WP REST nonce header `X-WP-Nonce`.
- Output is sanitized; inputs are validated and sanitized.

## Table schema

Table: `{$wpdb->prefix}library_books`

Columns:
- `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT (PRIMARY KEY)
- `title` VARCHAR(255) NOT NULL
- `description` LONGTEXT
- `author` VARCHAR(255)
- `publication_year` INT
- `status` ENUM('available','borrowed','unavailable') DEFAULT 'available'
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

## Development notes

- Database access uses `$wpdb` with prepared statements.
- Capability checks enforce `edit_posts` for write operations.
- The REST namespace is `library/v1`.


