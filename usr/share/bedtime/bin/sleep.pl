#!/usr/bin/perl
# This rewriter causes squid to rewrite every URL request
# to a link to the 'time to sleep' picture
$|=1;
print  "http://127.0.0.1/bedtime/sleep.php\n" while (<>);
