#! /bin/bash
MIRROR='http://cn.archive.ubuntu.com/ubuntu'
SECURITY_MIRROR=$MIRROR
export MIRROR SECURITY_MIRROR

if [ $# -lt 1 ]; then
	echo "Usage: `basename $0` hostname"
	exit 1
fi

lxc-create -n $1 -t ubuntu -B lvm -- -d -S /root/.ssh/id_rsa.pub
