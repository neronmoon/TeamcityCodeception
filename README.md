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