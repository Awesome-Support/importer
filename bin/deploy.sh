# Do not deploy yet.
exit 0;

ssh wordpress_awesome_support@awesome-support.presswa.re << EOF
  cd /var/www/awesome-support.presswa.re/wp-content/plugins
  curl -H "Authorization: token $GIT_TOKEN" -L https://api.github.com/repos/pressware/awesome-support-importer/tarball/master > awesome-support-importer.tar.gz
  rm -rf awesome-support-importer
  mkdir awesome-support-importer
  tar -xvf awesome-support-importer.tar.gz --strip 1 -C awesome-support-importer
  rm awesome-support-importer.tar.gz
EOF