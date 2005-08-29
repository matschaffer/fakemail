mkdir fakemail/
chmod +x fakemail
cp ../perl/fakemail fakemail/
cp ../LICENSE fakemail/
cp ../docs/index.html fakemail/
cp ../docs/docs.css fakemail/
tar -zcf fakemail.tar.gz fakemail/fakemail \
                         fakemail/LICENSE \
                         fakemail/docs/index.html \
                         fakemail/docs/docs.css
mv fakemail.tar.gz fakemail/
cd fakemail
