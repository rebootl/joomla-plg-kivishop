#!/usr/bin/perl -w
#
#
# understand the shopware connector
#
# get_categories function
#

# also need libjson-perl

#use utf8;
use JSON;
use open ':std', ':encoding(utf-8)';

use File::Basename;
use lib dirname (__FILE__);
use myJSON;

#my @daten      = @{$import->{data}};
#
# assuming @daten an array of categories
# using actual json output here

my $data_json = '{"err_msg":"","err_code":"","response_id":"5ba2480173d0c","api":"kivishop.categories","version":"","data":{"categories":[{"id":"5","active":"1","name":"Computer und Komplettsysteme","parentId":"0"},{"id":"130","active":"1","name":"Komplettsysteme","parentId":"5"},{"id":"2","active":"1","name":"Standard-PC","parentId":"5"},{"id":"44","active":"1","name":"Server","parentId":"5"},{"id":"43","active":"1","name":"Barebones, Mini-PCs","parentId":"5"},{"id":"42","active":"1","name":"MAC","parentId":"5"},{"id":"41","active":"1","name":"Workstation etc.","parentId":"5"},{"id":"40","active":"1","name":"Exoten","parentId":"5"},{"id":"129","active":"1","name":"Thin-Client","parentId":"5"},{"id":"30","active":"1","name":"Laptop und Zubeh\u00f6r","parentId":"0"},{"id":"29","active":"1","name":"Laptop","parentId":"30"},{"id":"39","active":"1","name":"Netbook","parentId":"30"},{"id":"126","active":"1","name":"Tablet PC","parentId":"30"},{"id":"97","active":"1","name":"Laptop-Steckkarte","parentId":"30"},{"id":"98","active":"1","name":"Laptop-Akku","parentId":"30"},{"id":"100","active":"1","name":"Docking-Station","parentId":"30"},{"id":"111","active":"1","name":"Laptop-Ersatzteile","parentId":"30"},{"id":"127","active":"1","name":"Laptop-Tasche","parentId":"30"},{"id":"38","active":"1","name":"Monitor, Beamer, Kamera","parentId":"0"},{"id":"36","active":"1","name":"Flachbildschirm","parentId":"38"},{"id":"154","active":"1","name":"TV","parentId":"38"},{"id":"37","active":"1","name":"R\u00f6hrenbildschirm","parentId":"38"},{"id":"7","active":"1","name":"Beamer","parentId":"38"},{"id":"27","active":"1","name":"Digital-Kamera","parentId":"38"},{"id":"71","active":"1","name":"Webcam","parentId":"38"},{"id":"110","active":"1","name":"Videokamera","parentId":"38"},{"id":"80","active":"1","name":"Drucker, Fax, Scanner...","parentId":"0"},{"id":"6","active":"1","name":"Drucker","parentId":"80"},{"id":"26","active":"1","name":"Multifunktionsger\u00e4t","parentId":"80"},{"id":"24","active":"1","name":"Scanner","parentId":"80"},{"id":"25","active":"1","name":"Faxger\u00e4t","parentId":"80"},{"id":"109","active":"1","name":"Druckereinzelteile","parentId":"80"},{"id":"128","active":"1","name":"Kopierer","parentId":"80"},{"id":"53","active":"1","name":"Tastatur, Maus, Spielsteuerung","parentId":"0"},{"id":"4","active":"1","name":"Tastatur","parentId":"53"},{"id":"3","active":"1","name":"Maus","parentId":"53"},{"id":"19","active":"1","name":"Spielsteuerung","parentId":"53"},{"id":"54","active":"1","name":"Graphik Tablett, Digital Pen","parentId":"53"},{"id":"82","active":"1","name":"Desktop-Set (Tastatur und Maus)","parentId":"53"},{"id":"55","active":"1","name":"Geh\u00e4use, Netzteile, Umschalter, USB-Hubs, USV, Serverracks","parentId":"0"},{"id":"1","active":"1","name":"PC-Geh\u00e4use","parentId":"55"},{"id":"56","active":"1","name":"Festplattengeh\u00e4use","parentId":"55"},{"id":"121","active":"1","name":"Festplatten-Schubladen","parentId":"55"},{"id":"57","active":"1","name":"Laufwerksgeh\u00e4use","parentId":"55"},{"id":"107","active":"1","name":"Geh\u00e4useteile","parentId":"55"},{"id":"61","active":"1","name":"Netzteil  extern","parentId":"55"},{"id":"63","active":"1","name":"Netzteil intern","parentId":"55"},{"id":"153","active":"1","name":"USV","parentId":"55"},{"id":"120","active":"1","name":"Umschalt-Switches","parentId":"55"},{"id":"147","active":"1","name":"USB-Hubs","parentId":"55"}]}}';

my $import = myJSON::decode_json($data_json);
#my $import = JSON->new->decode($data_json);

print $import->{data}->{categories};

my @daten = @{$import->{data}->{categories}};

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

#foreach $i (@daten[2]->{children}) {
#  print "@$i\n";
#}
