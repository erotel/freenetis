#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Std;
use Data::Dumper;
use warnings;

if ($#ARGV != 3) {
  print "Wrong number of parameters", "\n";
  exit 0;
}

use SOAP::Lite +autodispatch =>
   uri => "http://sip.nfx.czf/lBilling",
   proxy => 'https://'.$ARGV[0].':'.$ARGV[1].'@sip.nfx.czf/cgi-bin/admin/lbilling/soap.pl';

my $lbilling = lBilling->new();

unless ( $lbilling ) {
  print "Could not create SOAP instance", "\n";
  exit 0;
}

my $result;
my $i;

my $partner = {"from" => $ARGV[2], "to" => $ARGV[3]};

if ( $result = $lbilling->get_partner_calls($partner) ) {

   print $result->{"from"}, ";";
   print $result->{"to"}, "\n";

   unless ( $result->{"calls"} ) {
      print "0", "\n";
      exit 1;
   }

   my $count = @{$result->{"calls"}};

   print $count, "\n";

   for ($i=0; $i<$count; $i++)
   {
      print $result->{"calls"}[$i]->{"provider"}, ";";
      print $result->{"calls"}[$i]->{"cost_sum"}, ";";
      print $result->{"calls"}[$i]->{"subscriber"}, ";";
      print $result->{"calls"}[$i]->{"area"}, ";";
      print $result->{"calls"}[$i]->{"callee"}, ";";
      print $result->{"calls"}[$i]->{"status"}, ";";
      print $result->{"calls"}[$i]->{"rate_sum"}, ";";
      print $result->{"calls"}[$i]->{"emergency"}, ";";
      print $result->{"calls"}[$i]->{"callcon"}, ";";
      print $result->{"calls"}[$i]->{"caller"}, ";";
      print $result->{"calls"}[$i]->{"start_date"}, ";";
      print $result->{"calls"}[$i]->{"rate_vat"}, ";";
      print $result->{"calls"}[$i]->{"rate_curr"}, ";";
      print $result->{"calls"}[$i]->{"account"}, ";";
      print $result->{"calls"}[$i]->{"cost_vat"}, ";";
      print $result->{"calls"}[$i]->{"cost_curr"}, ";";
      print $result->{"calls"}[$i]->{"end_date"}, ";";
      print $result->{"calls"}[$i]->{"type"}, ";";
      print $result->{"calls"}[$i]->{"result"}, "\n";
   }
   exit 1;
} else {
   my $error = $lbilling->get_error();
   my $errcount = @{$lbilling->get_error()};
   for ($i=0; $i<$errcount; $i++)
   {
      print $lbilling->get_error()->[$i], "\n";
   }
   exit 0;
}
