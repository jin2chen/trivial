#!/bin/bash
ETH0_ADDRESS=192.168.1.13
ETH0_NETMASK=255.255.255.0
ETH0_GATEWAY=192.168.1.1
ETH1_ADDRESS=10.10.10.13
ETH1_NETMASK=255.255.255.0
WEB_ROOT_PATH=/var/www/html
NETBOOT_ROOT_PATH=$WEB_ROOT_PATH/netboot
UBUNTU_ISO_PATH=$WEB_ROOT_PATH/ubuntu
DNSMASQ_CONFIG=/etc/dnsmasq.conf
PRESEED_NAME=trusty.seed
PRESEED_CONFIG=$WEB_ROOT_PATH/$PRESEED_NAME

set -e

if [ `id -u` -ne 0 ] 
then
	echo 'You must run as root'
fi

cat > /etc/network/interfaces << EOT
# This file describes the network interfaces available on your system
# and how to activate them. For more information, see interfaces(5).

# The loopback network interface
auto lo
iface lo inet loopback

# The primary network interface
auto eth0
iface eth0 inet static
address $ETH0_ADDRESS
netmask $ETH0_NETMASK
gateway $ETH0_GATEWAY
dns-nameservers 8.8.8.8 202.106.46.151

auto eth1
iface eth1 inet static
address $ETH1_ADDRESS
netmask $ETH1_NETMASK
EOT
ifdow eth1 && ifup eth1

apt-get -y install dnsmasq atftp apache2-mpm-prefork
mkdir -p $WEB_ROOT_PATH
mkdir -p $NETBOOT_ROOT_PATH
mkdir -p $UBUNTU_ISO_PATH
umount $UBUNTU_ISO_PATH &> /dev/null || true
mount -o loop /dev/cdrom $UBUNTU_ISO_PATH
rm -rf $NETBOOT_ROOT_PATH/*
pushd $NETBOOT_ROOT_PATH
wget -O /tmp/netboot.tar.gz http://mirrors.163.com/ubuntu/dists/trusty/main/installer-amd64/current/images/netboot/netboot.tar.gz 
tar zxvf /tmp/netboot.tar.gz 
rm -f /tmp/netboot.tar.gz
rm -rf pxelinux.cfg
mkdir pxelinux.cfg
cat > pxelinux.cfg/default << EOT
default install
label install
     kernel ubuntu-installer/amd64/linux
     append DEBCONF_DEBUG=5 auto=true priority=critical url=http://$ETH1_ADDRESS/$PRESEED_NAME vga=788 initrd=ubuntu-installer/amd64/initrd.gz -- quiet 
EOT
popd

sed -e '/#--------------/,$d' -i $DNSMASQ_CONFIG
cat >> $DNSMASQ_CONFIG << EOT
#--------------
dhcp-range=$(echo $ETH1_ADDRESS | awk -F '.' "{printf \"%s.%s.%s.100,%s.%s.%s.150,$ETH1_NETMASK,12h\", \$1, \$2, \$3, \$1, \$2, \$3}")
dhcp-option=3,$ETH1_ADDRESS
dhcp-option=6,$ETH1_ADDRESS
enable-tftp
tftp-root=$NETBOOT_ROOT_PATH
dhcp-boot=pxelinux.0
EOT
service dnsmasq restart

cat > $PRESEED_CONFIG << 'EOT'
### Localization
d-i debian-installer/locale string en_US.utf8
d-i console-setup/ask_detect boolean false
d-i keyboard-configuration/layoutcode string us

### Network configuration
d-i netcfg/choose_interface select auto
d-i netcfg/get_hostname string uos1404
d-i netcfg/get_domain string local
d-i netcfg/wireless_wep string

### Mirror settings
d-i mirror/country string manual
d-i mirror/http/hostname string {{mirrors}}
d-i mirror/http/directory string /ubuntu
d-i mirror/http/proxy string
#d-i mirror/suite select trusty

### Clock and time zone setup
d-i clock-setup/utc boolean true
d-i time/zone string Asia/Shanghai
d-i clock-setup/ntp boolean true

### Partitioning
d-i partman-auto/method string lvm
d-i partman-lvm/device_remove_lvm boolean true
d-i partman-md/device_remove_md boolean true
d-i partman-lvm/confirm boolean true
d-i partman-partitioning/confirm_write_new_label boolean true
d-i partman/choose_partition select finish
d-i partman/confirm boolean true
d-i partman/confirm_nooverwrite boolean true
d-i partman-md/confirm boolean true
d-i partman-partitioning/confirm_write_new_label boolean true
d-i partman/choose_partition select finish
d-i partman/confirm boolean true
d-i partman/confirm_nooverwrite boolean true
d-i partman-lvm/confirm_nooverwrite boolean true
d-i partman-auto-lvm/new_vg_name string uos
d-i partman-basicfilesystems/no_mount_point boolean yes
d-i partman-auto/choose_recipe select atomic
#d-i partman-auto/choose_recipe select boot-root
d-i partman-auto/expert_recipe string               \
    boot-root ::                                    \
        256 256 256 ext2                            \
            $primary{ } $bootable{ }                \
            method{ format } format{ }              \
            use_filesystem{ } filesystem{ ext2 }    \
            mountpoint{ /boot }                     \
        .                                           \
        4096 4096 4096 ext4                         \
            $primary{ }                             \
            method{ format } format{ }              \
            use_filesystem{ } filesystem{ ext4 }    \
        .                                           \
        256 256 256 linux-swap                      \
            $lvmok{ }                               \
            method{ swap } format{ }                \
            lv_name{ swap }                         \
        .                                           \
        1 2 100000 ext4                             \
            $lvmok{ }                               \
            method{ format } format{ }              \
            use_filesystem{ } filesystem{ ext4 }    \
            lv_name{ root }                         \
            mountpoint{ / }                         \
        .

### Account setup
d-i passwd/user-fullname string mole
d-i passwd/username string mole
d-i passwd/user-password password mole
d-i passwd/user-password-again password mole
d-i passwd/user-uid string 10000
d-i user-setup/allow-password-weak boolean true
d-i user-setup/encrypt-home boolean false

### Apt setup
d-i apt-setup/backports boolean false
d-i apt-setup/security_host string {{mirrors}}
d-i apt-setup/security_path string /ubuntu
d-i apt-setup/no_mirror boolean true

### Package selection
tasksel tasksel/first multiselect server, openssh-server
#d-i pkgsel/update-policy select none
#d-i pkgsel/upgrade select none

### Boot loader installation
d-i grub-installer/only_debian boolean true
d-i grub-installer/with_other_os boolean true

### Kernel boot params
d-i debian-installer/quiet  boolean false
d-i debian-installer/splash boolean false

### Finishing up the installation
d-i finish-install/reboot_in_progress note
d-i debian-installer/exit/poweroff boolean true

### Bug fixed
# see http://ubuntuforums.org/showthread.php?t=2215103&p=13015013
d-i partman/early_command string umount /media || true; debconf-set partman-auto/disk "$(list-devices disk | head -n1)"
# see http://www.michaelm.info/blog/?p=1378
# If you use the server CD build the source package
d-i live-installer/net-image string http://{{mirrors}}/ubuntu/install/filesystem.squashfs
EOT
sed -e "s/{{\s*mirrors\s*}}/$ETH1_ADDRESS/g" -i $PRESEED_CONFIG

