#!/usr/bin/env python
#
# $Id: setup.py,v 1.1 2005/08/29 22:04:55 lastcraft Exp $


from distutils.core import *


setup(name="fakemail-python",
      version="1.0beta",
      author="Graham Ashton",
      author_email="ashtong@users.sourceforge.net",
      url="http://fakemail.sourceforge.net/",
      download_url="http://fakemail.sourceforge.net/",
      scripts=["fakemail.py"],
)
