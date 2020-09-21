<?php
declare(strict_types=1);

namespace Etermed\Ewus\Parsers;

use Etermed\Ewus\Contracts\Parser as ParserContract;
use Etermed\Ewus\Contracts\Response;
use Etermed\Ewus\Responses\LoginResponse;
use Etermed\Ewus\Support\Xml;
use Exception;

class LoginParser extends Parser implements ParserContract
{
    public function parse(Xml $xml): Response
    {
        $xml->registerNamespace('auth');

        [$loginCode, $loginMessage] = $this->parseLoginData($xml);

        $response = new LoginResponse($this->request);

        $response->setSessionId($this->parseSessionId($xml));
        $response->setToken($this->parseToken($xml));
        $response->setLoginCode($loginCode);
        $response->setLoginMessage(html_entity_decode($loginMessage, ENT_NOQUOTES));

        return $response;
    }

    private function parseSessionId(Xml $xml): string
    {
        $node = $xml->get('//com:session');

        if ($node === null) {
            throw new Exception();
        }

        return $node->getAttribute('id');
    }

    private function parseToken(Xml $xml): string
    {
        $node = $xml->get('//com:authToken');

        if ($node === null) {
            throw new Exception();
        }

        return $node->getAttribute('id');
    }

    /**
     * Get login data (code and message).
     *
     * @param   \Etermed\Ewus\Support\Xml  $xml
     * @return  string[]
     */
    private function parseLoginData(Xml $xml): array
    {
        $pattern = '/^\[(?<code>[0-9]{3})\] (?<message>.+)$/';
        $data    = $this->parseLoginRawData($xml);

        if (preg_match($pattern, $data, $matches) !== 1) {
            throw new Exception();
        }

        return [
            $matches['code'] ?? '',
            $matches['message'] ?? '',
        ];
    }

    private function parseLoginRawData(Xml $xml): string
    {
        $node = $xml->get('//auth:loginReturn');

        if ($node === null) {
            throw new Exception();
        }

        return $node->nodeValue;
    }
}