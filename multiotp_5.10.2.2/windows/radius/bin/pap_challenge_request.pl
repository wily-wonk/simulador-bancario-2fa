#!/bin/perl -w

# Thomas Glanzmann 16:06 2012-05-21
# First Argument is username, second argument is password
# Authen::Radius requires a legacy dictionary without advanced
# keywords like encrypted or $INCLUDEs

use strict;
use warnings FATAL => 'all';

# use Term::ReadPassword;
use Term::ReadPassword::Win32;
use Authen::Radius;

my %response_codes = (
        1   =>   'Access-Request',
        2   =>   'Access-Accept',
        3   =>   'Access-Reject',
        4   =>   'Accounting-Request',
        5   =>   'Accounting-Response',
        11  =>   'Access-Challenge',
        12  =>   'Status-Server (experimental)',
        13  =>   'Status-Client (experimental)',
        255 =>   'Reserved',

);

my $username = $ARGV[0];
my $password = $ARGV[1];

unless (defined($username)) {
        print "Enter username: ";
        $username = <STDIN>;
        chomp($username);
}

unless (defined($password)) {
        $password = read_password('Enter password: ');
}

my $r = new Authen::Radius(Host => '127.0.0.1', Secret => 'testing123');
Authen::Radius->load_dictionary('sms_otp_dictionary');

$r->add_attributes (
                { Name => 'User-Name', Value => $username },
                { Name => 'User-Password', Value => $password },
);

$r->send_packet(ACCESS_REQUEST)  || die;
my $type = $r->recv_packet();

print "server response type = $response_codes{$type} ($type)\n";

exit 1 unless $type == 11;

my $state = undef;

for $a ($r->get_attributes()) {
        if ($a->{Name} eq 'State') {
                $state = $a->{RawValue};
        }
}

print "Enter otp: ";
my $otp = <STDIN>;
chomp($otp);

$r->add_attributes (
                { Name => 'User-Name', Value => $username },
                { Name => 'User-Password', Value => $otp },
);

$r->send_packet(ACCESS_REQUEST)  || die;
$type = $r->recv_packet();

print "server response type = $response_codes{$type} ($type)\n";

exit 1 unless $type == 2;
