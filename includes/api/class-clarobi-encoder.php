<?php

/**
 * Encodes JSON data.
 *
 * @link       https://clarobi.com
 * @since      1.0.0
 *
 * @package    Clarobi
 * @subpackage Clarobi/includes/api
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Encodes JSON data.
 *
 * This class is responsible for encoding JSON data for security purpose.
 *
 * @since      1.0.0
 * @package    Clarobi
 * @subpackage Clarobi/includes/api
 * @author     Interlive Metrics <gitangeorgiana97@gmail.com>
 */
class Clarobi_Encoder
{
    protected $jsonContent;
    protected $encodedJson;
    protected $configs;

    /**
     * Clarobi_Encoder constructor.
     *
     * @param array $configs
     */
    public function __construct($configs)
    {
        $this->configs = $configs;
    }

    /**
     * Format final json and encrypt data.
     * Must be called by each derived class after specific mapping.
     *
     * @param string $entityName Entity name.
     * @param null $data Entity data.
     * @param int $lastId Last entity id returned.
     * @param null $type Entity will be sync or called 1/day.
     * @return array
     */
    public function encodeJson($entityName, $data, $lastId = 0, $type = null)
    {
        $this->jsonContent = $data;

        $responseIsEncoded = $responseIsCompressed = false;

        // Encode and compress the data only if we have it
        if (!empty($data)) {
            $encoded = $this->encode($data);

            if (is_string($encoded)) {
                $responseIsEncoded = true;
                $data = $encoded;
            }

            $compressed = $this->compress($encoded);
            if ($compressed) {
                $responseIsCompressed = true;
                $data = $compressed;
            }
        }

        $this->encodedJson = [
            'isEncoded' => $responseIsEncoded,
            'isCompressed' => $responseIsCompressed,
            'data' => $data,
            'license_key' => $this->configs['CLAROBI_LICENSE_KEY'],
            'entity' => $entityName,
            'type' => ($type ? $type : 'SYNC')
        ];
        // Add lastId only for sync entities only
        if ($lastId) {
            $this->encodedJson['lastId'] = $lastId;
        }

        return $this->encodedJson;
    }

    /**
     * Compress encoded data if lib and functions exist.
     *
     * @param $data
     * @return string
     */
    public function compress($data)
    {
        if (extension_loaded('zlib') &&
            function_exists('gzcompress') &&
            function_exists('base64_encode')
        ) {
            return base64_encode(gzcompress(serialize(($data))));
        } else {
            Clarobi_Logger::errorLog('Extensions zlib or gzcompress or base64_encode do not exist', __METHOD__);
        }

        return false;
    }

    /**
     * Encode data with API_SECRET from configuration.
     *
     * @param $payload
     * @return string
     */
    public function encode($payload)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt(json_encode($payload), 'aes-256-cbc', $this->configs['CLAROBI_API_SECRET'], 0, $iv);

        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decode data with API_SECRET from configuration.
     *
     * @param $payload
     * @return string
     */
    public function decode($payload)
    {
        list($encryptedData, $iv) = explode('::', base64_decode($payload), 2);

        return json_decode(openssl_decrypt($encryptedData, 'aes-256-cbc', $this->configs['CLAROBI_API_SECRET'], 0, $iv));
    }

}