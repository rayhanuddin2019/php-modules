<?php

namespace Mecha\Modular\Example\modules;

use DateTime;
use Exception;
use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Services\Value;
use Mecha\Modular\Services\Service;
use Mecha\Modular\Services\StringValue;
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
            'timezone' => new Value('Asia/Tokyo'),

            // The URL from which to request the time
            'request_url' => new StringValue('http://worldtimeapi.org/api/timezone/{tz}', [
                'tz' => 'timezone',
            ]),

            // The function that gets the time
            'time' => new Service(['request_url'], function ($url) {
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
