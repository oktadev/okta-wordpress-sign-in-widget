# WordPress Okta Sign-In Widget

Replaces the WordPress login screen with the Okta sign-in widget

This is just a proof of concept!

TODO:

* Clean up the UX around installing the plugin, like making sure the admin user can still log in after the plugin is activated
* Handle errors better (or at all really)

## Development Environment

### Manual

- Install WordPress
- Download and unzip from github
- For standard WordPress installs move the plugin to `wp-content/plugins` directory
- For WordPress installs using [Bedrock](https://github.com/roots/bedrock) move plugin to `web/app/plugins` directory

### Docker

- [Install Docker](https://docs.docker.com/install/)
- Install [Docker Compose](https://docs.docker.com/compose/install/)
- Run `docker-compose up` 
- Navigate to [http://localhost:8080](http://localhost:8080)