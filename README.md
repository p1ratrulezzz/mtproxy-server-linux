# MTProxy Server for Telegram clients

This is just a useful scripts for setting up your own mtprotocol proxy for telegram. Note: this proxy once created can be used by other users who you shared this proxy with and it doesn't give them access to your account, so its completely safe to use it and share with friends!
Plus: Sponsored channels are coming. That means that your proxy can force your users to subscribe to some channel. This can be as a motivation to setup such a proxy.

# Installation

The daemon file is https://github.com/danog/MadelineProto/blob/master/mtproxyd (don't forget to Star this repo)

## Tutorial for Ubuntu 16.04 Server

0. Loggining in via SSH to your VDS as root
1. Install requirements

```bash
   apt-get update
   apt-get install php7.0-cli php7.0-xml php7.0-curl php7.0-opcache
```

2. Create mtproxy user

```bash
  useradd --shell /usr/sbin/nologin -M -u 3000 -o mtproxy
```

Note: password you set doesn't matter

3. Create a directory for proxy daemon

```bash
  mkdir /opt/mtproto-proxy
``

4. Download and run proxy for the first time

```bash
  cd /opt/mtproto-proxy
  wget -O mtproxyd 'https://raw.githubusercontent.com/danog/MadelineProto/master/mtproxyd'
  chmod +x mtproxyd
  ./mtproxyd marcopolo 6666
```
6666 - is the port to listen on
marcopolo - is the seed to generate hashes. Its quite safe to leave it this way.

You will see that daemon is running and it will write on first lines:

```
  your secret phrase abcdefghihfhasfasfsf
```

abcdefghihfhasfasfsf - is the Secret (some kind of a password) used to auth on your proxy server. Keep this in secret, copy to some place.

5. Install a linux service to start/stop/restart and autostart proxy

5. Final steps. Check permissions for files

```bash
  chown /opt/mtproto-proxy mtproxy:mtproxy
```
