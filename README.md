# COVE Asset Manager

The COVE Asset Manager is a plugin to allow the integration of PBS's COVE video system with Wordpress posts.

## Features

* Provides a space for the user to associate a COVE Partner Player ID with any post 
* Provides a space for the user to associate a Youtube Video ID with the same post
* Displays either the COVE video or the YouTube video in the post, depending on availability
* Includes a tool for submitting video ingest jobs to COVE with all appropriate metadata and retrieve the corresponding Partner Player ID
* Includes a tool for uploading the required assets for the COVE ingest to an Amazon S3 bucket
* Includes a tool for uploading a video to YouTube with all appropriate metadata and retrieving the corresponding Youtube ID

## Contents

The COVE Asset Manager includes the following files:

* A subdirectory named `classes`. This represents the core plugin files.
* A subdirectory named `assets`.

## Installation

1. Copy the `cove-asset-manager` directory into your `wp-content/plugins` directory
2. Navigate to the *Plugins* dashboard page
3. Locate the menu item that reads *TODO*
4. Click on *Activate*
5. Navigate to *Settings* and select*Cove Asset Manager Settings* 
6. Enter your COVE API Keys, COVE Batch API Keys, Amazon S3 Keys, and the google account that owns your YouTube channel.
7. Edit or create a post and click in the *COVE/Youtube Video Asset* form for that post to associate a video asset with that post.

## License

The COVE Asset Manager is licensed under the GPL v2 or later.

> This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

> This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

> You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

## Important Notes

