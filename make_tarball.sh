chmod +x fakemail
cd ..
tar -zcf fakemail.tar.gz fakemail/fakemail \
                         fakemail/LICENSE \
                         fakemail/docs/index.html \
                         fakemail/docs/docs.css
mv fakemail.tar.gz fakemail/
