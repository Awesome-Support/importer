<?php

namespace Pressware\AwesomeSupport\Traits;

trait CastToTrait
{
    public function toJSON($toBeConverted)
    {
        return json_encode($toBeConverted);
    }

    public function fromJSON($json)
    {
        return json_decode($json);
    }

    public function toArray($value)
    {
        return (array)$value;
    }

    public function toObject(array $array)
    {
        return (object)$array;
    }

    public function toInt($string)
    {
        return (int)$string;
    }

    public function toFormattedDate($timestamp, $dateFormat = 'Y-m-d H:i:s')
    {
        return (new \DateTime($timestamp))->format($dateFormat);
    }

    public function toTimestamp($date)
    {
        return strtotime($date);
    }
}
