<?php
declare(strict_types=1);
function https_redirect_target(array $settings,array $server): ?string {
    if(empty($settings['require_https'])) return null;
    // Do not trust client-supplied forwarded headers. The selected host must set HTTPS.
    $https=strtolower((string)($server['HTTPS']??''));
    if(in_array($https,['on','1'],true)||(string)($server['SERVER_PORT']??'')==='443') return null;
    $url=parse_url((string)($settings['registration_url']??''));
    if(!$url||($url['scheme']??'')!=='https'||empty($url['host'])||isset($url['user'])||isset($url['pass'])) throw new DomainException('Configure a valid HTTPS registration URL before enabling HTTPS enforcement.');
    $uri=(string)($server['REQUEST_URI']??'/');
    if(!str_starts_with($uri,'/')||preg_match('/[\r\n]/',$uri)) $uri='/';
    return 'https://'.$url['host'].(isset($url['port'])?':'.$url['port']:'').$uri;
}
