#!/usr/bin/perl
#
#
# understand the shopware connector
#
# get_categories function
#

#my @daten      = @{$import->{data}};
#
# assuming @daten an array of categories
# creating a pseudo-array
my @daten = ( { id => 5,
                name => "Computer",
                parentId => 3 },
              { id => 7,
                name => "Drucker",
                parentId => 3 },
              { id => 3,
                name => "Muell",
                parentId => 0 }
            );



my %categories = map { ($_->{id} => $_) } @daten;

for(@daten) {
  my $parent = $categories{$_->{parentId}};
  $parent->{children} ||= [];
  push @{$parent->{children}},$_;
}

### debug print an array of hashes
foreach $el (@daten) {
  print "$el\n";
  foreach $key (keys %{$el}) {
    print "  $key -> " . $el->{$key} . "\n";
  }
}

foreach $i (@daten[2]->{children}) {
  print "@$i\n";
}
