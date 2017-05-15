# Deployer

It takes repository and uploads non-ignored files to remote server


## How to use it

1. Go to your project directory
1. Run `composer require --dev netpromotion/deployer`
2. Create [`deploy.json` file](#deployjson)
3. Configure it the same way as [`dg/ftp-deployment`]
4. Private properties (f.e. `remote`) extract into [`deploy.local.json` file](#deploylocaljson)
5. Run `./vendor/bin/deploy`

## Examples


### deploy.json
```json
{
  "test": true,
  "log": {
    "config": "/deploy.config.log"
  },
  "ignore": [
    "/tests/",
    "!/vendor/"
  ],
  "before": [
    "local: git tag -f deployed",
    "local: git push -f origin deployed",
    "local: composer install"
  ],
  "after": [
    "http://server.tld/deployed.php"
  ]
}
```

### deploy.local.json
```json
{
  "test": false,
  "remote": "ftp://user:password@server.tld/directory"
}
```



[`dg/ftp-deployment`]:https://github.com/dg/ftp-deployment
