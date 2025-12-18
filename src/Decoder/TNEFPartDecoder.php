<?php

namespace Webklex\PHPIMAP\Decoder;

use TNEFDecoder\TNEFAttachment;
use Webklex\PHPIMAP\{Part, Header};

class TNEFPartDecoder
{
    public function __construct(private $headers = null, private $config = null) {}

    public function decode($payload, $part_number): mixed
    {
        if ($this->getEncoding() === 'base64') {
            $content = base64_decode($payload);
        }
        $attachment = new TNEFAttachment();
        $attachment->decodeTnef($content);
        $files = $attachment->getFiles();
        $parts = [];
        foreach ($files as $file) {
            $parts[] = new Part(base64_encode($file->content), $this->config, $this->createFakeHeaders($file), $part_number++);
        }
        return $parts;
    }

    public function getEncoding(): string
    {
        return $this->headers->get('content_transfer_encoding')();
    }

    public static function isTNEFEncoded($headers): bool
    {
        return $headers->get("content_type")->contains("application/ms-tnef");
    }

    private function createFakeHeaders($file)
    {
        $fileName = $file->name ?? 'winmail.dat';
        $fileType = $file->type ?? 'application/ms-tnef';
        $fakeHeaders = [
            'Content-Type' => "$fileType; name=\"$fileName \"",
            'Content-Description' => $fileName,
            'Content-Disposition' => 'attachment; filename=\"' . $fileName . '\"',
            'content_transfer_encoding' => 'base64',
        ];
        $headers = implode(
            "",
            array_map(function ($key, $value) {
                return $key . ": " . $value . "\r\n";
            }, array_keys($fakeHeaders), $fakeHeaders)
        );


        return new Header($headers, $this->config);
    }
}
