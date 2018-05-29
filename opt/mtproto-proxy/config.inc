<?php
      
// additional configs for server. Check options in mtproxyd file

return [
  'server' => [
    'address' => '0.0.0.0', // Enter public Ipv4 address or leave as is.
    'handler' => '\danog\MadelineProto\Server\ProxyCustom',
  ],
  // Configure chaining proxy server to use for connection to the telegram servers
  // For example, if your server blocks connections to telegram servers (like OVH, for example)     
  'proxy' => [
      'enabled' => false, // if set to false, proxy not used
      'type' => 'socks5', // or http,
      'address' => '', // address of the proxy,
      'auth' => [
        'username' => 'username',
        'password' => 'password',
      ],
  ],
];
