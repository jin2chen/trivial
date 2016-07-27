#!/usr/bin/perl -w
#
# $Id: svn-pre-commit.pl 675 2010-10-29 10:17:42Z coelho $
#
# TODO
# - size? (svnlook cat ... | wc -c)
# - continuations in configuration file?
# - pre-*lock, pre-revprop-change?
# - look for conflict markers (<... -... >...) on text files

use strict;

=head1 NAME

B<svn-pre-commit.pl> - configurable C<pre-commit> hook for subversion.

=head1 SYNOPSIS

B<svn-pre-commit.pl> [B<--help>, B<--man> or other options] B<REPOS TXN>

=head1 DESCRIPTION

This script performs sanity checks about what is being committed.
It is designed to be run directly or indirectly as a subversion
C<pre-commit> hook script.
It is configured from an INI file.
The main point of the script is to I<think again> before committing:
it can be configured to be forcible based on the log message contents.

=head1 OPTIONS

=over 4

=item B<--configuration=inifile> or B<-c inifile>

Configuration file to use. Default is to use C<conf/svn-pre-commit.conf>
if available within the repository, so that the script can be invoked directly
as a C<pre-commit> hook by subversion. See C<-g> option below.

=item B<--default> or B<-d>

Use default configuration. Do not look for any INI file.

=item B<--generate-configuration> or B<-g>

Generate the current configuration file on standard output.
This can be redirected to a file and tailored as needed.

  sh> svn-pre-commit.pl -g > /path/to/repos/conf/svn-pre-commit.conf
  # then edit configuration file to your taste

=item B<--help> or B<-h>

This help.

=item B<--man> or B<-m>

More help.

=item B<--set section.parameter=value> or B<-s sec.par=val>

Override the default value of a parameter in a section.

=item B<--svnlook=/path/to/svnlook> or B<-l /path/to/svnlook>

Specify explicitely which C<svnlook> command to use.

=item B<--verbose> or B<-v>

Be verbose. Repeat for more.

=item B<--version> or B<-V>

Show script revision and exit.

=back

=head1 ARGUMENTS

The script expects two arguments, which are mandatory but for
help, man, version, and configuration generation options.

=over 4

=item B<REPOS>

Directory path to the subversion repository.

=item B<TXN>

Transaction or revision number.
The revision number can be used to test the script configuration on prior
commits, to check for what would have been done with it.

=back

=head1 INVOCATION

This script may be invoked:

=over 4

=item B<directly as a hook script>

for instance by linking it as C<pre-commit>, in which case the configuration
is looked for as C<conf/svn-pre-commit.conf> within the repository, or the
default configuration is used.

=item B<indirectly from a hook script>

in which case the hook script may invoke other checks, and additional options
can be passed.

=item B<directly from a shell>

For testing, for instance  to check what would be the result on a previous
revision in a given repository.

=back

=head1 CONFIGURATION

The configuration is stored in an INI file.
The current configuration can be regenerated with the B<-g> option described
above.
The output may be redirected to a file and modified to suit your needs.
Use this option to see detailed parameters for each section.

The available sections are:

=over 4

=item B<general>

General settings, including whether the commit can be forced with some
keyword in the log message, and for each check whether it is activated.
Part of the repository can be ignored by the script.

=item B<replace>

Check for replaced files.

=item B<mergeinfo>

Filter out multi-level C<svn:mergeinfo> properties on a path.

=item B<path>

Check allowed characters in file and directory paths.

=item B<filename>

Filter filenames.

=item B<dirname>

Filter dirnames.

=item B<emptydir>

Filter out I<added> empty directories.

=item B<suffix>

Check for allowed or denied suffixes.

=item B<showinfo>

Append revision information about this script to generated message.

=item B<svnprops>

Check svn:* file or directory property names.

=item B<otherprops>

Check non-svn file or directory property names.

=item B<propvalues>

Check property values.

=item B<svnrevprops>

Check C<svn:*> revision property names.

=item B<otherrevprops>

Check non-svn revision property names.

=item B<revpropvalues>

Check revision property values, for instance the log message.

=back

=head1 LICENSE

=for html
<img src="http://www.gnu.org/graphics/gplv3-127x51.png"
alt="GNU GPLv3" align="right" />

(c) 2009-2010 Fabien Coelho <svn-pre-commit at coelho dot net>
L<http://www.coelho.net/>

This is free software, both inexpensive and available with sources.
The GNU General Public License v3 or more applies (GPLv3+).
The brief summary is: You get as much as you paid for, and I am not
responsible for anything.
See L<http://www.gnu.org/copyleft/gpl.html> for details.

=head1 DOWNLOAD

The latest version of the script is available at
L<http://www.coelho.net/svn-pre-commit.pl>.

=head1 INSTALLATION

Just put the script in your path, or copy it directly as a C<pre-commit> hook.

The script relies on three perl modules: C<Config::IniFiles>, C<Getopt::Long>,
and C<Pod::Usage>. Although the two later modules are pretty standard, the
former one may need to be installed via your packaging system or using CPAN.
For instance, package C<libconfig-inifiles-perl> is needed on Debian/Ubuntu.
See your system administrator.

=head1 BUGS

All softwares have bugs, this is a software, hence...

Maybe the implementation would be more efficient in python, using bindings
which access the repository contents directly instead on relying on svnlook.

The script is not nicely extensible, say with some clever object-oriented
interface or the like.
It is just plain C<perl>.
I like that.

Running C<svnlook pl> on revision properties within a transaction does not
seem to work with subversion 1.5. There is a partial workaround.

People do not like their commits to be rejected. Really.
They usually do not read the error message.
Enforcing a commit policy has a weak pedagogical value when a deadline is
coming and the urgent work is bounced.
You won't get many thanks for deploying such a script.

=head1 VERSION

This documentation is about $Revision: 675 $ of the script.

=cut

my $KEYWORDS =
  'Id|Rev(ision)?|(Head)?URL|Date|Author|LastChanged(By|Date|Revision)|Header';

# describe the expected syntax
# - section name => {
#       'doc' => section-documentation,
#       parameter-name => [ default-value, parameter-documentation ], ...
#   }
# this approach helps generate the default configuration file directly from
# the script, hence no additional file needs to be distributed, this files
# contains the code, the configuration and the documentation.
my %SYN = (
  'general' => {
    # section documentation
    'doc' => 'general settings',
    # expected parameters
    'force' => [ '',
	'exact string in log to force accept (empty is disabled)' ],
    'ignore' => [ '',
	'left anchored regexpr of path to ignore within the repository' ],
    'showinfo' => [ 'on',
	 'append revision information about svn-pre-commit.pl hook' ],
    'explain' => [ 'if a legitimate addition is blocked, let us discuss it.',
		   'final message if commit is denied' ],
    # activate/deactivate checks
    # miscellaneous
    'replace' => [ 'on', 'activate replace check' ],
    'mergeinfo' => [ 'on', 'activate mergeinfo check' ],
    # names
    'filename' => [ 'on', 'activate filename check' ],
    'dirname' => [ 'on', 'activate dirname check' ],
    'emptydir' => [ 'on', 'activate emptydir check' ],
    'path' => [ 'on', 'activate path check' ],
    'suffix' => [ 'on', 'activate suffix check' ],
    # props
    'svnprops' => [ 'on', 'activate svnprops check' ],
    'otherprops' => [ 'on', 'activate non-svn props check' ],
    'propvalues' => [ 'on', 'activate property value check' ],
    # rev props
    'svnrevprops' => [ 'on', 'activate svn revprop check' ],
    'otherrevprops' => [ 'on', 'activate non-svn revprop check' ],
    'revpropvalues' => [ 'on', 'activate revision property value check' ]
#    '' => [ 'on', 'activate  check' ]
  },
  'filename' => {
    'doc' => 'file name constraints',
    'allow' => [ '', 'regexpr of allowed filenames' ],
    'deny' => [ 'core|TAGS|CTAGS|a\.out', 'regexpr of denied filenames' ],
    'message' => [ 'filename not allowed', 'error message on reject' ],
    'explain' => [ 'filenames must meet some constraints', 'full explanation' ]
  },
  'dirname' => {
    'doc' => 'directory name constraints',
    'allow' => [ '', 'regexpr of allowed dirnames' ],
    'deny' => [ 'RCS|CVS|\.svn|\.git', 'regexpr of denied dirnames' ],
    'message' => [ 'dirname not allowed', 'error message on reject' ],
    'explain' => [ 'dirnames must meet some constraints', 'full explanation' ]
  },
  'emptydir' => {
    'doc' => 'no empty directory',
    'match' => [ '.*', 'regexpr of directories to check' ],
    'ignore' => [ '', 'regexpr of directories to ignore' ],
    'message' => [ 'empty directory', 'error message on reject' ],
    'explain' => [ 'added directories must not be empty', 'full explanation' ]
  },
  'suffix' => {
    'doc' => 'suffix constraints',
    'allow' => [ '', 'regexpr of allowed suffixes' ],
    'deny' => [ 'o|a|so|dll|pyc|aux|log|tmp|dvi|class|bak|info|' .
		'tar|zip|gz|bz2|tgz|jar|deb|rpm',
		'regexpr of denied suffixes' ],
    'message' => [ 'suffix not allowed', 'error message on reject' ],
    'explain' => [ 'suffixes must meet some constraints', 'full explanation' ]
  },
  'path' => {
    'doc' => 'path constraints',
    'chars' => [ '-a-zA-Z_0-9\.', 'list of allowed chars in path'],
    'message' => [ 'non ASCII path', 'error message on reject'],
    'explain' => [ 'all path must be in ASCII', 'full explanation' ]
  },
  'mergeinfo' => {
    'doc' => 'check for multiple mergeinfo on path',
    'message' => [ 'multiple mergeinfo on path', 'error message on reject' ],
    'explain' => [ 'multiple mergeinfo breaks later merges',
		   'full explanation' ]
  },
  'replace' => {
    'doc' => 'check for replaced files',
    'max' => [ 3, 'maximum replacements allowed, or "any"' ],
    'message' => [ 'too many replaced files', 'error message on reject' ],
    'explain' => [ 'replacements (rm+add) break history', 'full explanation' ]
  },
  'svnprops' => {
    'doc' => 'check svn property names',
    'allow' => [ 'keywords|mergeinfo|special|executable|mime-type|externals|' .
		 'ignore|eol-style|needs-lock',
		 'regexpr of svn:* props '],
    'message' => [ 'unexpected svn property', 'error message on reject' ],
    'explain' => [ 'use only standard svn:* property names',
		   'full explanation' ]
  },
  'otherprops' => {
    'doc' => 'check non-svn property names',
    'allow' => [ '', 'regexpr for non-svn props' ],
    'message' => [ 'unexpected property', 'error message on reject' ],
    'explain' => [ 'some property names are constrained', 'full explanation' ]
  },
  'propvalues' => {
    'doc' => 'check property values',
    'message' => [ 'unexpected property value', 'error message on reject' ],
    'explain' => [ 'some property values are constrained',
		   'full explanation' ],
    'svn:eol-style' => [ 'native|LF|CR|CRLF',
			 'regexpr for svn:eol-style value'],
    'svn:mime-type' => [ '(text|image|audio|video|application)/[-a-z0-9\.]+',
			 'regexpr for svn:mime-type value' ],
    'svn:executable' => [ '[1\*]', 'regexpr for svn:executable value' ],
    'svn:keywords' => [ "($KEYWORDS|)(\\s+($KEYWORDS))*",
			'regexpr for svn:keywords value' ]
  },
  'svnrevprops' => {
    'doc' => 'check svn revision property names',
    'allow' => [ 'log|author|date|check-locks', 'regexpr of svn:* revprops' ],
    'message' => [ 'unexpected svn revprop', 'error message on reject' ],
    'explain' => [ 'use only standard svn:* revprop names',
		   'full explanation' ]
  },
  'otherrevprops' => {
    'doc' => 'check non-svn revision property names',
    'allow' => [ '', 'regexpr for non-svn revprops' ],
    'message' => [ 'unexpected revprop', 'error message on reject' ],
    'explain' => [ 'some revprop names are constrained', 'full explanation' ]
  },
  'revpropvalues' => {
    # section documentation
    'doc' => 'check revision property values',
    # expected parameters
    'message' => [ 'unexpected revprop value', 'error message on reject' ],
    'explain' => [ 'some revision property values are constrained',
		   'full explanation' ],
    'svn:author' => [ '[-a-z]+', 'anchored regexpr for svn:author value' ],
    'svn:log' => [ '.*\S.*', 'anchored regexpr for svn:log value' ]
  }
  # sizes
);

# set some defaults
my $svnlook = 'svnlook';
my $verb = 0;
my $configuration = '';
my $genconf = 0;

# be verbose depending on current level
# verb 3 "some message"
# output '### some message' if current verbosity is 3 or more
sub verb($@)
{
  my ($level, @msg) = @_;
  print STDERR '#' x $level, " @msg\n" if $level<=$verb;
}

# tell whether configuration parameter is "true"
sub is_true($)
{
  my ($b) = @_;
  die "unexpected boolean value $b"
    unless $b =~ /^\s*(yes|no|on|off|true|false|1|0|)\s*$/i;
  return $b =~ /^\s*(yes|on|true|1)\s*$/i;
}

use Pod::Usage;
use Getopt::Long qw(:config no_ignore_case);

# svn revision number
my $rev = '$Revision: 675 $';
$rev =~ tr/0-9//cd;

# handle options
GetOptions(
  "configuration|c=s" => \$configuration,
  "svnlook|l=s" => \$svnlook,
  "verbose|v+" => \$verb,
  "default|d" => sub { $configuration = undef },
  "help|h" => sub { pod2usage(-verbose => 1); },
  "man|m" => sub { pod2usage(-verbose => 2); },
  "generate-configuration|gen-conf|gc|g" => \$genconf,
  "set|s=s" => sub {
    my ($opt,$v) = @_;
    if ($v =~ /^([-a-z]+)\.([-a-z]+)=(.*)$/)
    {
      if (exists $SYN{$1}{$2}) {
	my $type = ref $SYN{$1}{$2};
	if ($type eq 'ARRAY') {
	  ${$SYN{$1}{$2}}[0] = $3;
	}
	elsif ($type eq 'SCALAR') {
	  $SYN{$1}{$2} = $3;
	}
	else {
	  die "unexpected reference type $type while in --set $1.$2=...";
	}
      }
      else {
	die "no parameter $2 in section $1";
      }
    }
    else {
      die "unexpected section.parameter=value syntax: $v";
    }
  },
  "version|V" => sub { print "$0 revision $rev\n"; exit 0; })
    or pod2usage(-verbose=>0, -exitval=>1, -msg=>'invalid option');

verb 2, "handling arguments";

# check & get hook arguments
# there may be no arguments under -g which is expected to exit
# so the remainder of the initialization phase must be permissive enough
# for that. The rational is that -g may read the configuration if available,
# but will show the default configuration if not.
pod2usage(-verbose => 0, -exitval=>2, -msg=>'expecting two arguments')
  unless $genconf or @ARGV == 2;

# get arguments (may be empty under genconf)
my ($REPOS, $TXN) = @ARGV;

# is it a repository?
die "no such directory: $REPOS"
  unless $genconf or defined $REPOS and -d $REPOS;

die "directory does not look like a subversion repository: $REPOS"
  unless $genconf or defined $REPOS and -f "$REPOS/format" and -d "$REPOS/db";

verb 2, "handling configuration";

# configuration hash: { section => { parameter => value } }
my %conf;

# get initial configuration
if (defined $configuration or
    defined $REPOS and -f "$REPOS/conf/svn-pre-commit.conf")
{
  $configuration = "$REPOS/conf/svn-pre-commit.conf"
    if not $configuration and
       defined $REPOS and -f "$REPOS/conf/svn-pre-commit.conf";

  if ($configuration)
  {
    die "no configuration file '$configuration'"
      unless -f $configuration;

    use Config::IniFiles;
    tie %conf, 'Config::IniFiles', (-file => $configuration)
      or die "cannot read configuration '$configuration'";
  }
}

# set defaults if not set by tie
for my $section (keys %SYN)
{
  $conf{$section} = {}
    unless exists $conf{$section};
  for my $param (keys %{$SYN{$section}})
  {
    next if $param eq 'doc';
    $conf{$section}{$param} = $SYN{$section}{$param}[0]
      unless exists $conf{$section}{$param};
  }
}

# check section/parameter names and report unexpected ones.
for my $section (keys %conf)
{
  for my $param (keys %{$conf{$section}})
  {
    die "unexpected parameter $param in section $section"
      unless $SYN{$section}{$param} or
	     $section =~ /^(rev)?propvalues$/ and
	     $param =~ /^[-a-z]+:[-a-z]+$/;
  }
}

# show the current configuration to stdout, and exit
if ($genconf)
{
  print "# svn-pre-commit current configuration\n\n";
  for my $section (sort keys %conf) {
    print "# ", $SYN{$section}{doc}, "\n";
    print "[$section]\n";
    for my $param (sort keys %{$conf{$section}}) {
      # skip section documentation
      next if $param eq 'doc';
      # show all parameters
      print "# ", $SYN{$section}{$param}[1], "\n";
      print "$param=", $conf{$section}{$param}, "\n";
    }
    print "\n";
  }
  exit 0;
}

# help testing manually with a revision number
my $opt = '--transaction';
$opt = '--revision' if $TXN =~ /^[0-9]+$/;

#
# DO THE JOB
#

# revision's informations
my $author = `$svnlook author $opt $TXN '$REPOS'`;
chomp $author;

my $log = `$svnlook log $opt $TXN '$REPOS'`;
chomp $log;

# all found errors for the final report
my @errors = ();

# keep track of refusals, per category
# category => count
my %niets = ();

# message
sub niet($@)
{
  my ($case, @msg) = @_;
  $niets{$case}++;
  push @errors, ${conf{$case}}{message} . (@msg? " @msg": '') . "\n";
}

# memoization
# trunk is always assumed to have mergeinfo.
my %has_mergeinfo = ('trunk' => 1);

# returns whether path has an 'svn:mergeinfo' prop,
# with memoization in %has_mergeinfo
sub has_mergeinfo($)
{
  my ($path) = @_;

  if (!defined $has_mergeinfo{$path})
  {
    my $pl = `$svnlook pl $opt $TXN '$REPOS' '$path'`;
    $has_mergeinfo{$path} = ($pl =~ /\bsvn:mergeinfo\b/);
  }

  return $has_mergeinfo{$path};
}

# returns whether there are several mergeinfo props
sub has_several_mergeinfo($)
{
  my ($path) = @_;
  my $nmi = 0; # number of svn:mergeinfo found on path
  my $p = $path;
  $p =~ s/\/$//;
  do {
    $nmi++ if has_mergeinfo $p;
  }
  while $p =~ s/\/[^\/]*$//;

  return $nmi>1;
}

# force commit...
if (${conf{general}}{force})
{
  my $force = ${conf{general}}{force};
  exit 0 if $log =~ /$force/;
}

# record all operations
# path => ops-letters
my %operation = ();

# counts add/update operations in directory
# dir-path => count
my %add_in_dir = ();

open CHANGED, "$svnlook changed $opt $TXN '$REPOS' |"
  or die "cannot '$svnlook changed ...' ($!)";

# read out "changed" report
while (<CHANGED>)
{
  chomp;
  die "unexpected '$svnlook changed' ouput" unless /^(...) (.*)$/;
  my ($what, $path) = ($1, $2);

  # record
  $operation{$path} .= $what;

  # deleted files can be anything...
  next if $what =~ /^D/;

  # record added stuff & dir
  if ($what =~ /^A/ and $path =~ /\/$/) {
    $add_in_dir{$path} = 0;
  }

  if ($what =~ /^[AU]/ and $path !~ /\/$/) {
    my $d = $path;
    $d =~ s/[^\/]+$//;
    $add_in_dir{$d}++ if exists $add_in_dir{$d};
  }

  # may ignore some parts (prefix)...
  next if $conf{general}{ignore} and $path =~ /^$conf{general}{ignore}/;

  # check file names
  if (is_true $conf{general}{filename})
  {
    my $deny = $conf{filename}{deny};
    niet 'filename', $path
      if $deny and $path =~ /\/($deny)$/;

    my $allow = $conf{filename}{allow};
    niet 'filename', $path
      if $allow and $path !~ /\/($allow)$/;
  }

  # check directory names
  if (is_true $conf{general}{dirname})
  {
    my $deny = $conf{dirname}{deny};
    niet 'dirname', $path
      if $deny and $path =~ /\/($deny)\/$/;

    my $allow = $conf{dirname}{allow};
    niet 'dirname', $path
      if $allow and $path !~ /\/($allow)\/$/;
  }

  # check suffixes
  if (is_true $conf{general}{suffix})
  {
    my $deny = $conf{suffix}{deny};
    niet 'suffix', $path
      if $deny and $path =~ /\.($deny)\/?$/;

    my $allow = $conf{suffix}{allow};
    niet 'suffix', $path
      if $allow and $path !~ /\.($allow)\/?$/;
  }

  # some file system or locale do not like non ascii filenames...
  # this also filters out emacs temporary files (*~), rcs files (*,v).
  if (is_true $conf{general}{path})
  {
    my $chars = ${conf{path}}{chars};
    niet 'path', $path
      if $chars and $path !~ /^[${chars}\/]+$/;
  }

  # this would break svn merge book-keeping
  if (is_true $conf{general}{mergeinfo})
  {
    niet 'mergeinfo', $path
      if has_several_mergeinfo($path);
  }

  if (is_true $conf{general}{svnprops} or
      is_true $conf{general}{otherprops} or
      is_true $conf{general}{propvalues})
  {
    my $allow = $conf{svnprops}{allow};
    my $othallow = $conf{otherprops}{allow};

    # check svn property names
    for my $pn (`$svnlook plist $opt $TXN '$REPOS' '$path'`)
    {
      $pn =~ s/^\s*([-a-z:]+)\s*$/$1/s;
      if (is_true $conf{general}{svnprops} and $pn =~ /^svn:/)
      {
	niet 'svnprops', "$pn $path"
	  if $allow and $pn !~ /^svn:(${allow})$/;
      }
      if (is_true $conf{general}{otherprops} and $pn !~ /^svn:/)
      {
	niet 'otherprops', "$pn $path"
	  if $othallow and $pn !~ /^(${othallow})$/;
      }
      if (is_true $conf{general}{propvalues} and exists $conf{propvalues}{$pn})
      {
	my $val = `$svnlook pget $opt $TXN '$REPOS' $pn '$path'`;
	my $re = $conf{propvalues}{$pn};
	niet 'propvalues', "$pn=$val $path"
	  unless $val =~ /^$re$/;
      }
    }
  }
}

close CHANGED
  or die "cannot close '$svnlook changed ...' ($!)";

if (is_true $conf{general}{svnrevprops} or
    is_true $conf{general}{otherrevprops} or
    is_true $conf{general}{revpropvalues})
{
  my $allow = $conf{svnrevprops}{allow};
  my $othallow = $conf{otherrevprops}{allow};
  # check svn revision property names
  my %seen_revprop = ();
  my @rpl = ('svn:log', 'svn:author', 'svn:date',
	     `$svnlook plist --revprop $opt $TXN '$REPOS'`);
  for my $pn (@rpl)
  {
    $pn =~ s/^\s*([-a-z:]+)\s*$/$1/s;

    # hmmm... issues with the plist above on transactions in 1.5
    next if exists $seen_revprop{$pn};
    $seen_revprop{$pn} = 1;

    if (is_true $conf{general}{svnrevprops} and $pn =~ /^svn:/)
    {
      niet 'svnrevprops', "$pn"
	if $allow and $pn !~ /^svn:(${allow})$/;
    }
    if (is_true $conf{general}{otherrevprops} and $pn !~ /^svn:/)
    {
      niet 'otherrevprops', "$pn"
	if $othallow and $pn !~ /^(${othallow})$/;
    }
    if (is_true $conf{general}{revpropvalues} and
	exists $conf{revpropvalues}{$pn})
    {
      my $val = `$svnlook pget --revprop $opt $TXN '$REPOS' $pn`;
      my $re = $conf{revpropvalues}{$pn};
      niet 'revpropvalues', "$pn=$val"
	unless $val =~ /^$re$/s;
    }
  }
}

# return whether some upper directory was "just added".
sub just_added_path($)
{
  my ($dir) = @_;
  while ($dir =~ s/\/[^\/]+\/?$//)
  {
    return 1 if exists $operation{"$dir/"} && $operation{"$dir/"} eq 'A  ';
  }
  return 0;
}

if (is_true $conf{general}{replace})
{
  # check for "replaced" files
  my @replaced = ();
  for my $path (sort keys %operation)
  {
    push @replaced, $path
      if $operation{$path} =~ /D.*A/ &&
      # hmmm... it seems that "svnlook" may reported formerly deleted files
      # in a directory which is just added, if the said directory was removed
      # sometime before. This seems to occur in 1.5.x but not in 1.6.x.
      ! just_added_path($path);
  }
  die "unexpected max value in replace section, not an integer"
    unless $conf{replace}{max} =~ /^\s*\d+\s*$/;
  # replacing files break the file history...
  if (@replaced > $conf{replace}{max}) {
    for my $path (@replaced) {
      niet 'replace', $path;
    }
  }
}

# check for added empty directories
if (is_true $conf{general}{emptydir})
{
  for my $dir (sort keys %add_in_dir)
  {
    next if exists $conf{emptydir}{ignore} and
	$conf{emptydir}{ignore} =~ /\S/ and
	$dir =~ /$conf{emptydir}{ignore}/;
    next if exists $conf{emptydir}{match} and
	$conf{emptydir}{match} =~ /\S/ and
	$dir !~ /$conf{emptydir}{match}/;
    # re-check that added directory is empty, because when a
    # directory is moved, it is shown as added and empty by "svnlook changed"
    next if `$svnlook tree $opt $TXN '$REPOS' "$dir" | wc -l` > 1;
    if ($add_in_dir{$dir} == 0) {
	niet 'emptydir', $dir;
    }
  }
}

# report all the found issues
my $nerrors = @errors;

# build a compact summary
my $summary = '';
for my $issue (sort keys %niets) {
  $summary .= "$issue=$niets{$issue} ";
}

# message header
my @msg =
   ("repos: $REPOS\n",
    "transaction: $TXN\n",
    "errors: $nerrors\n",
    "summary: $summary\n",
    "author: $author\n",
    "log: $log\n",
    "reminder:\n");

# detailed messages only if pertinent
for my $section (sort keys %niets)
{
  push @msg, " - " . $conf{$section}{explain} . "\n";
}

# full message
push @msg, "\n$conf{general}{explain}\n\ndetails:\n", @errors
  if @errors;

# append revision info
push @msg, "\nthese bad news were brought to you by $0 (revision $rev)\n"
  if is_true $conf{general}{showinfo};

# cleanup
untie %conf;

# final result
die @msg if @errors;
