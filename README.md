## Jenkins Annotator [![Build Status](http://ci.shonm.com/job/ShonM_jenkins-annotator/badge/icon)](http://ci.shonm.com/job/ShonM_jenkins-annotator/)

Inspired by [the original jenkins-commentator by KnpLabs](https://github.com/KnpLabs/jenkins-commentator)

This spin-off is written in PHP using React and Symfony/Console just because.

Configure your job to call this post-build:

```
curl --data-urlencode out@${WORKSPACE}/result.testdox "localhost:8090\
?jenkins=$JENKINS_URL
&user=ShonM\
&repo=jenkins-annotator\
&sha=$GIT_COMMIT\
&status=$BUILD_STATUS\
&project=$JOB_NAME\
&job=$BUILD_NUMBER"
```

What's happening is the contents of the file `${WORKSPACE}/result.testdox` is sent upstream as the "out" variable. The content of this file can be anything you want Annotator to comment on the pull request with. One example would be PHPUnit's --testdox output, which is a human-readable version of its regular output. The choice is yours.

With the EnvInject Plugin, under Build > Inject environment variables > Properties Content, pass: `BUILD_STATUS=success`
