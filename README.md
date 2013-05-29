## Jenkins Annotator

Inspired by [the original jenkins-commentator by KnpLabs](https://github.com/KnpLabs/jenkins-commentator)

### Installation
 1. Put jenkins-annotator behind anything that can run PHP. Nginx, apache, or the built-in server are all good options.
 2. Install the Github Pull Request Builder
 3. Install the Groovy Postbuild plugin
 4. Add the following Groovy Postbuild script to your project
 5. Make sure you change the user, repo, token, and annotator locations!


```
// Definitely modify these
def user = "user"
def repo = "repository"
def token = "access token"
def annotator = "127.0.0.1:9400"

// Don't touch these
def env = manager.build.getEnvironment(manager.listener)
def url = env['BUILD_URL']
def sha = env['ghprbActualCommit']
def workspace = env['WORKSPACE']

// Hit annotator
"curl ${annotator}/${user}/${repo}/${sha} --data 'url=${url}&status=${manager.build.result}&token=${token}' --data-urlencode 'out@${workspace}/test'".execute()
```
