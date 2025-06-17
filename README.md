## Project Setup

1. Clone repository
2. Run composer install
3. Run `./vendor/bin/sail up -d`

## Project Standards

1. Always have a PR to commit code to `development`
2. Never force push to `development` or `master`

## Releasing
- Releases will be made off of `development` and then merged into `master`
  - Following versioning nomiclature i.e. ( release/v1.0.0 )
- After merge, do not delete the release branch!!
  - it's there to hold previous versions of the product
