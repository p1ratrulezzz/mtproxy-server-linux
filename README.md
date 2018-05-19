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
marcopolo - is the seed to generate hashes. Its quite safe to leave it this way. It might ask you to login using your existing telegram account. This is ok, just enter your phone number and then an autorization code that you will get via Telegram. This is just a one time setup.

You will see that daemon is running and it will write on first lines:

```
  your secret phrase abcdefghihfhasfasfsf
```

abcdefghihfhasfasfsf - is the Secret (some kind of a password) used to auth on your proxy server. Keep this in secret, copy to some place.

Now press Ctrl+C to terminate the proxy process.

5. Check permissions for files

```bash
  chown /opt/mtproto-proxy mtproxy:mtproxy
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

Note: you should edit the file /etc/init.d/mtproxy and set your own directory/seed/port if you're an experienced user.

7. Test your installation on telegram desktop client which can be downloaded from https://github.com/telegramdesktop/tdesktop/releases

(use Alpha release as it is the only release availalble that supports MTproto proxies)

Server: your_ip_address_or_domain_name Port: 6666
Secret: your_secret_from_step_4
