# TeamcityCodeception
Integrates Codeception and Teamcity CI using service-messages
https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity
### Installation:
Add require string to composer.json
```json
...
"require-dev": {
	"neronmoon/teamcity-codeception": "dev-master",
}
...
```
and add
```yaml
extensions:
  enabled:
    ...
    - Codeception\Extension\TeamCity
    ...
```
to your codeception.yml file

# Known issues
If you are using custom runner script, php may not detect env variables. So you just need to add *CI* env var to you server (```export CI=CI```) or to your runner script