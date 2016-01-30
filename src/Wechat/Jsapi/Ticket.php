<?php

namespace Thenbsp\Wechat\Wechat\Jsapi;

use Thenbsp\Wechat\Bridge\Http;
use Thenbsp\Wechat\Bridge\CacheTrait;
use Thenbsp\Wechat\Wechat\AccessToken;
use Thenbsp\Wechat\Wechat\Exception\JsapiTicketException;

class Ticket
{
    /**
     * Cache Trait
     */
    use CacheTrait;

    /**
     * http://mp.weixin.qq.com/wiki/11/74ad127cc054f6b80759c40f77ec03db.html（附录 1）
     */
    const JSAPI_TICKET = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';

    /**
     * Jsapi 票据类型
     */
    const TICKET_TYPE = 'jsapi';

    /**
     * Thenbsp\Wechat\Wechat\AccessToken
     */
    protected $accessToken;

    /**
     * 构造方法
     */
    public function __construct(AccessToken $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * 获取 AccessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * 获取 Jsapi 票据（调用缓存，返回 String）
     */
    public function getTicketString()
    {
        $cacheId = $this->getCacheId();

        if( $this->cache && $data = $this->cache->fetch($cacheId) ) {
            return $data['ticket'];
        }

        $response = $this->getTicketResponse();

        if( array_key_exists('errcode', $response)
            && ($response['errcode'] != 0) ) {
            throw new JsapiTicketException($response['errmsg'], $response['errcode']);
        }

        if( $this->cache ) {
            $this->cache->save($cacheId, $response, $response['expires_in']);
        }

        return $response['ticket'];
    }

    /**
     * 获取 Jsapi 票据（不缓存，返回原始数据）
     */
    public function getTicketResponse()
    {
        $query = array('type' => static::TICKET_TYPE);

        $response = Http::request('GET', static::JSAPI_TICKET)
            ->withAccessToken($this->accessToken)
            ->withQuery($query)
            ->send();

        return $response;
    }

    /**
     * 从缓存中清除
     */
    public function clearFromCache()
    {
        return $this->cache
            ? $this->cache->delete($this->getCacheId())
            : false;
    }

    /**
     * 获取缓存 ID
     */
    public function getCacheId()
    {
        return sprintf('%s_jsapi_ticket', $this->accessToken['appid']);
    }
}
