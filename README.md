superseriousstats
=================

A small and efficient program written in PHP for creating a Web page with statistics out of various types of IRC logs. The program keeps track of its parse history and only processes new activity before storing any accumulated data in a MySQL database.

Suitable for high volume IRC channels and large log archives. Once you have superseriousstats running it is relatively easy to set up your own IRC services (e.g. bots) to interact with the database and provide last seen information and many other statistics directly in your channel.

superseriousstats is released under the [ISC license] (http://opensource.org/licenses/isc-license.txt).

Source
------

The source code is available at [GitHub] (http://github.com/tommyrot/superseriousstats).

    git clone git://github.com/tommyrot/superseriousstats.git

Releases
--------

After significant changes have been made there follows a release. Releases are tested and the best option for obtaining a working version. Download the latest version [here] (http://code.google.com/p/superseriousstats/downloads/list).

Demo
----

You can view a [live demo] (http://sss.dutnie.nl) made for the superseriousstats support channel. It is a low volume channel which updates once a day.

Documentation
=============

All documentation can be found on the [Wiki] (http://code.google.com/p/superseriousstats/w/list).

Contributing
============

We are glad to help you get the program running. We are also happy to hear of any success stories.

Issues
------

Bug reports and feature requests can be filed [here] (http://code.google.com/p/superseriousstats/issues/list). Keep it real.

Logfile formats
---------------

If you want superseriousstats to also support the logfile format you use, you can create your own parser file. Alternatively you can convince me to make one for you where you take on the task of testing the results. It helps if you can provide an overview of the (default) logging syntax used by the client.

Support
-------

You are welcome to join us in #sss-support on irc.quakenet.org.
