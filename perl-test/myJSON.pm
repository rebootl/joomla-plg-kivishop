package myJSON;

use strict;

use JSON ();

use parent qw(Exporter);
our @EXPORT = qw(encode_json decode_json to_json from_json);

sub new {
  shift;
  return JSON->new(@_)->convert_blessed(1);
}

sub encode_json {
  return JSON->new->convert_blessed(1)->encode(@_);
}

sub decode_json {
  goto &JSON::decode_json;
}

sub to_json {
  my ($object, $options)      = @_;
  $options                  ||= {};
  $options->{convert_blessed} = 1;
  return JSON::to_json($object, $options);
}

sub from_json {
  goto &JSON::from_json;
}

1;
