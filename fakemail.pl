#!/usr/bin/perl
#
# $Id: fakemail.pl,v 1.4 2005/02/09 22:57:36 lastcraft Exp $
#
use Net::Server::Mail::SMTP;
use IO::Socket::INET;
use Getopt::Long;
use POSIX;

# Bail out if missing parameters.
#
my ($host, $port, $path, $background);
GetOptions(
        'host=s'      => \$host,
        'port=i'      => \$port,
        'path=s'      => \$path,
        'background'   => \$background);

if (! defined($host) or ! defined($port) or ! defined($path)) {
    die "Usage: ./fakemail.pl\n" .
    "       --host=<localdomain>\n" .
    "       --port=<port number>\n" .
    "       --path=<path to save mails>\n" .
    "       --background\n";
};
$path =~ s|/$||;

# Run in background.
#
if ($background) {
    exit if my $child = fork;
    die ($!) unless defined ($child);
    POSIX::setsid() or die ('Cannot detach from session: $!');
    print "$$\n";
    $SIG{INT} = $SIG{TERM} = $SIG{HUP} = \&signals;
}

# Start SMTP server.
#
{
    my @local_domains = ($host);
    my $server = new IO::Socket::INET Listen => 1, LocalPort => $port;
    my $socket;

    while ($socket = $server->accept) {
        my $smtp = new Net::Server::Mail::SMTP socket => $socket;
        $smtp->set_callback(RCPT => \&validate_recipient);
        $smtp->set_callback(DATA => \&queue_message);
        $smtp->process();
        $socket->close();
        $socket = undef;
    }
    $server->close();
    exit;

    # Event handlers.
    #
    sub validate_recipient {
        my($session, $recipient) = @_;
        return (1);
    }

    sub queue_message {
        my ($session, $data) = @_;

        my $sender = $session->get_sender();
        my @recipients = $session->get_recipients();
        foreach my $recipient (@recipients) {
            open(FILE, "> " . get_filename_from_recipient($recipient));
            print FILE ${$data};
            close(FILE);
        }
        return (1, 250, "message queued");
    }

    sub signals {
        if (defined($socket)) {
            $socket->close();
        }
        $server->close();
        exit;
    }
}

# Helpers
#
{
    my %counts = ();

    sub get_filename_from_recipient {
        my $recipient = shift;

        if (! defined($counts{$recipient})) {
            $counts{$recipient} = 1;
        }
        return "$path/$recipient." . $counts{$recipient}++;
    }
}
