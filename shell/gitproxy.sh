#!/bin/sh
# apt-get install connect-proxy
# ssh -N  mole1230@5.usssh.com -D 0.0.0.0:7070
#[core]
#       gitproxy = /path/to/socks5proxywrapper
# OR 
#export GIT_PROXY_COMMAND="/path/to/socks5proxywrapper"
#export HTTPS_PROXY=socks5://127.0.0.1:7070
connect -S 127.0.0.1:7070 "$@"
