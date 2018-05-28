# MTProxy Server for Telegram clients

[![Latest Stable Version](https://poser.pugx.org/p1ratrulezzz/mtproxy-server-linux/v/stable)](https://packagist.org/packages/p1ratrulezzz/mtproxy-server-linux)
[![Latest Unstable Version](https://poser.pugx.org/p1ratrulezzz/mtproxy-server-linux/v/unstable)](https://packagist.org/packages/p1ratrulezzz/mtproxy-server-linux)

This is just a useful scripts for setting up your own mtprotocol proxy for telegram. Note: this proxy once created can be used by other users who you shared this proxy with and it doesn't give them access to your account, so its completely safe to use it and share with friends!
Plus: Sponsored channels are coming. That means that your proxy can force your users to subscribe to some channel. This can be as a motivation to setup such a proxy.

# Before you start installation

Some VPS providers are blocking Telegram connections from their servers. Here is the list of these services and it will be filled in during some time, but for now there are already these ones:

* OVH
* IPhoster

Don't use these providers for your MTProto proxy server.

# Installation

The initial daemon file is in https://github.com/danog/MadelineProto (don't forget to Star this repo)

## Tutorial for Ubuntu 16.04 Server

0. Loggining in via SSH to your VDS as root
1. Install requirements

```bash
   apt-get update
   apt-get install php7.0-cli php7.0-xml php7.0-curl php7.0-opcache php7.0-zip
   apt-get install composer
```

2. Create mtproxy user

```bash
  useradd --shell /usr/sbin/nologin -M -u 3000 -o mtproxy
```

Note: password you set doesn't matter

3. Download a package into /opt folder

```bash
  cd /opt/
  composer require p1ratrulezzz/mtproxy-server-linux ^0.1
  sh vendor/p1ratrulezzz/mtproxy-server-linux/install.sh
``

After that you will see that the directory /opt/mtproto-proxy has been created.

4. Run proxy for the first time

```bash
  cd /opt/mtproto-proxy
  chmod +x mtproxyd
  ./mtproxyd marcopolo 6666
```
6666 - is the port to listen on
marcopolo - is the seed to generate hashes. It is safe to leave it this way as created hash will be random anyway. But it is better toset your own seed and use for that the one of the generated from [Random.org](https://www.random.org/passwords/?num=5&len=16&format=html&rnd=new) password of any length. It might ask you to login using your existing telegram account. This is ok, just enter your phone number and then an autorization code that you will get via Telegram. This is just a one time setup.

You will see that daemon is running and it will create a file secret.txt.

```
  cat secret.txt
```

Note: you can manualy create this file and write your own 32 character string in it. In this case it won't be rewritten.

it will show you your secret password for connecting to your proxy. Something like:
```
   38f75d6bb9f8138e53489cf1b7a132ec
```

38f75d6bb9f8138e53489cf1b7a132ec - is the 32 characters secret (some kind of a password) used to auth on your proxy server. This password will be written to secret.txt file and you always can reveal it if you have root access. The easiest way to creat your own password is to use md5 hash of any of provided string using any md5 generator (online tools are also OK).

Note: the password can be set manualy. It MUST be any HEX-string (only digits and characters from range a-f and always lowercased). This key is used to encrypt your traffic using AES encryption.

Now press Ctrl+C to terminate the proxy process.

5. Check permissions for files

```bash
  chown -R mtproxy:mtproxy /opt/mtproto-proxy
```

6. Install a linux service to start/stop/restart and autostart proxy

```bash
   wget -O /etc/init.d/mtproxy 'https://raw.githubusercontent.com/p1ratrulezzz/mtproxy-server-linux/master/etc/init.d/mtproxy'
   chmod +x /etc/init.d/mtproxy
   systemctl daemon-reload
   systemctl start mtproxy
   systemctl enable mtproxy
```

And check if it is working

```bash
   systemctl start mtproxy
```

You should see that it is "active (running)"

```
 mtproxy.service - LSB: MTProxy service
   Loaded: loaded (/etc/init.d/mtproxy; bad; vendor preset: enabled)
   Active: active (running) since Thu 2018-05-17 21:55:08 UTC; 1 day 9h ago
     Docs: man:systemd-sysv-generator(8)
  Process: 16657 ExecStop=/etc/init.d/mtproxy stop (code=exited, status=0/SUCCESS)
  Process: 16752 ExecStart=/etc/init.d/mtproxy start (code=exited, status=0/SUCCESS)
    Tasks: 14
   Memory: 55.3M
      CPU: 1min 14.837s
   CGroup: /system.slice/mtproxy.service

```

Note: you should edit the file /etc/init.d/mtproxy and set your own seed and port.

7. Test your installation
* Using telegram desktop client which can be downloaded from https://github.com/telegramdesktop/tdesktop/releases
* Using Android client that can be downloaded from https://t.me/tgbeta/2975

(use Alpha release as it is the only release availalble that supports MTproto proxies)

Server: your_domain_name_or_ip_address_of_vds_server Port: 6666
Secret: secret_from_secret.txt_file

Note: ip address of VDS/VPS server can be revealed by running a command

```bash
   curl ipinfo.io/ip
```

or (may not be usefull on servers that have internal IP but this is not our case so this should be OK)
```
   ifconfig
```   

## Other proxy server implementations

Check this account https://github.com/mtProtoProxy to see all the MTProto proxy implementations.

It is possible to use daemon script from this repo to integrate with any of these servers (except nodejs, for nodejs use pm2 upstart)

## Another version of installator for MTProxy (madeline driven)

Check the repo: https://github.com/aquigni/MTProxySystemd . It provides an easy-install shell script. It installs the same version of MTProto Proxy as provided in this [repo](https://github.com/danog/MadelineProto)

