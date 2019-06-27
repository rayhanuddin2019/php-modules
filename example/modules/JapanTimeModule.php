<?php

namespace Mecha\Modular\Example\modules;

use DateTime;
use Exception;
use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Services\Config;
use Mecha\Modular\Services\Factory;
use Mecha\Modular\Services\StringConfig;
use Psr\Container\ContainerInterface;
use function curl_close;
use function curl_error;
use function json_decode;

/**
 * A module that can fetch the current time in Japan, specifically Tokyo.
 */
class JapanTimeModule implements ModuleInterface
{
    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c)
    {
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getFactories()
    {
        return [
            // The timezone name to use
            'jap_time/timezone' => new Config('Asia/Tokyo'),

            // The URL from which to request the time
            'jap_time/request_url' => new StringConfig('http://worldtimeapi.org/api/timezone/{tz}', [
                'tz' => 'jap_time/timezone',
            ]),

            // The function that gets the time
            'jap_time/time' => new Factory(['jap_time/request_url'], function ($url) {
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                ]);

                $result = curl_exec($curl);

                if (!$result) {
                    throw new Exception(curl_error($curl));
                }

                $data = json_decode($result);
                if ($data === null || !isset($data->datetime)) {
                    throw new Exception('Malformed response: ' . $result);
                }

                curl_close($curl);

                $datetime = new DateTime($data->datetime);

                return $datetime->format('H:i:s jS F');
            }),
        ];
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getExtensions()
    {
        return [];
    }
}
