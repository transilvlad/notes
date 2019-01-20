# Maven Release Notes

Collects Jira tickets from commits between Maven releases.

## Help
```
./notes.php -h
Maven Release Notes 1.0 by Vlad Marian <transilvlad@gmail.com>

Usage: notes.php [-h][-v][-p][-l][--list][--show][--from]

Optional:
   -h           Show help
   -v           Show version
   -p           Project directory path
   -l           Logfile name
  --list        List latest releases
  --show        Show selected version changes
  --from        Show changes from this version onwards
```
## List latest versions (max 10)
```
$ ./notes.php --list
Versions:
2.4.3
2.4.2
2.4.1
2.4.0
2.3.28
```
## Show version commits
```
$ ./notes.php --show 2.4.5
Tickets:
SUP-2012
SUP-2014

Changes:
Updated to Junit 5
Ready for Open JDK 11

```
## Git log
```
$ git log --pretty=format:"[%h] %s"
[a1c10a5] [maven-release-plugin](transilvlad)prepare release 2.4.5
[f04d519] Merge branch 'SUP-2012' into 'master'
[0c12cc3] SUP-2012
[bba1f8b] Merge branch 'SUP-2014' into 'master'
[bbd6623] SUP-2014
[927675b] Updated to Junit 5
[6f7d8c6] Ready for Open JDK 11
[9180a46] [maven-release-plugin](transilvlad)prepare for next development iteration
[0a980a1] [maven-release-plugin](transilvlad)prepare release 2.4.4
[716eab6] Merge branch 'SUP-2015' into 'master'
[b798e2b] SUP-2015
[b932cb0] Merge branch 'SUP-2017' into 'master'
[b798e2b] SUP-2017
[b932cb0] Merge branch 'SUP-2019' into 'master'
[b798e2b] SUP-2019
[76e5749] Added more assertions
[7830d3f] [maven-release-plugin](transilvlad)prepare for next development iteration
[54287b1] [maven-release-plugin](transilvlad)prepare release 2.4.3

```

