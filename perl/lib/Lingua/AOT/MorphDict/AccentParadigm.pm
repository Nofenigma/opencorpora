package Lingua::AOT::MorphDict::AccentParadigm;

use strict;
use warnings;
use utf8;

our $VERSION = "0.01";

 
sub new {
  my ($class, $line) = @_;
  my $self = {};

  @{$self->{forms}} = split(/;/, $line);

  bless($self, $class);
  return $self;
} 

sub GetLastFormNo {
  my $self = shift;
  return $#{$self->{forms}};
}
