mkdir fakemail/
cp ../perl/fakemail fakemail/
chmod +x fakemail/fakemail
cp ../LICENSE fakemail/
cp ../docs/index.html fakemail/
cp ../docs/docs.css fakemail/
tar -zcf fakemail.tar.gz fakemail/fakemail \
                         fakemail/LICENSE \
                         fakemail/index.html \
                         fakemail/docs.css
