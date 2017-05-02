# Deployer

It takes repository and uploads non-ignored files to remote server

## How to use it

1. Go to your project directory
1. Run `composer require --dev netpromotion/deployer`
2. Create `deploy.json` file
3. Configure it the same way as [`dg/ftp-deployment`]
4. Private properties (f.e. `remote`) extract into `deploy.local.json` file
5. Run `./vendor/bin/deploy`
