## Project Setup

1. Clone repository
2. Run composer install
3. Run `./vendor/bin/sail up -d`
4. Run tests by `./vendor/bin/pest` or `./vendor/bin/sail artisan test`

### Testing Environment Setup
- Make a `.env.testing` file and copy the contents from `.env.testing.example`
- Change `DB_HOST` to `testDB` ( This will make sure you are connecting to the docker testDB )


## Project Standards

1. Always have a PR to commit code to `development`
2. Never force push to `development` or `master`

## Releasing
- Releases will be made off of `development` and then merged into `master`
  - Following versioning nomiclature i.e. ( release/v1.0.0 )
- After merge, do not delete the release branch!!
  - it's there to hold previous versions of the product
