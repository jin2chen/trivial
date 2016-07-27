#!/bin/bash
# aptitude install ubuntu-virt-server python-vm-builder
set -e

SCRIPT_DIR=$(cd $(dirname $0) && pwd)
TARGET_BASE_DIR=/var/lib/libvirt/images
TARGET_DIR=$TARGET_BASE_DIR/$1
INTERFACE_FILE=$SCRIPT_DIR/interfaces
HOSTNAME=$1
MEMORY=512
CPUS=2
ROOTSIZE=2048
IP=$2
MASK=255.255.255.0
GW=192.168.1.1
DNS="8.8.8.8 202.106.46.151"
TEMPLATES=$SCRIPT_DIR/tpl
ISO=$SCRIPT_DIR/ubuntu-12.04.3-server-amd64.iso
ARCH=amd64
MIRROR=http://cn.archive.ubuntu.com/ubuntu
USER=mole
SUITE=precise
TIMEZONE=Asia/Shanghai
LIBVIRT=qemu:///system
BRIDGE=br0
ADDPKGS=" \
	--addpkg=acpid \
	--addpkg=bash-completion \
	--addpkg=vim-nox \
	--addpkg=openssh-server \
"

if [ `id -u` -ne 0 ]
then
	echo 'You must run as root.'
	exit 1;
fi

if [ -z "$(dpkg --get-selections | grep -P 'ubuntu-virt-server\s*install$')" ]
then
	aptitude -y install ubuntu-virt-server python-vm-builder
	cp -f $INTERFACE_FILE /etc/network
	/etc/init.d/networking restart
fi

if [ $# -lt 2 ]
then
	echo "Usage: `basename $0` hostname ip"
	exit 1
fi

mkdir -p $TARGET_DIR
pushd $TARGET_DIR

vmbuilder kvm ubuntu -o -v --debug \
	--templates=$TEMPLATES \
	--tmpfs=- \
	--rootsize=$ROOTSIZE \
	--swapsize=$MEMORY \
	--domain=$HOSTNAME.com \
	--arch=$ARCH \
	--hostname=$HOSTNAME \
	--user=$USER \
	--name=$USER \
	--pass=$USER \
	--rootpass=$USER \
	--suite=$SUITE \
	--flavour=virtual \
	--iso=$ISO \
	--mirror=$MIRROR \
	--security-mirror=$MIRROR \
	--timezone=$TIMEZONE \
	--mem=$MEMORY \
	--cpus=$CPUS \
	--ip=$IP \
	--mask=$MASK \
	--gw=$GW \
	--dns="$DNS" \
	--libvirt=$LIBVIRT \
	--bridge=$BRIDGE \
	$ADDPKGS 2>&1 | tee log

popd
