<?php

namespace Stripe;

abstract class WebhookSignature
{
    const EXPECTED_SCHEME = 'v1';

    
    public static function verifyHeader($payload, $header, $secret, $tolerance = null)
    {
        
        $timestamp = self::getTimestamp($header);
        $signatures = self::getSignatures($header, self::EXPECTED_SCHEME);
        if (-1 === $timestamp) {
            throw Exception\SignatureVerificationException::factory(
                'Unable to extract timestamp and signatures from header',
                $payload,
                $header
            );
        }
        if (empty($signatures)) {
            throw Exception\SignatureVerificationException::factory(
                'No signatures found with expected scheme',
                $payload,
                $header
            );
        }

        
        
        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = self::computeSignature($signedPayload, $secret);
        $signatureFound = false;
        foreach ($signatures as $signature) {
            if (Util\Util::secureCompare($expectedSignature, $signature)) {
                $signatureFound = true;

                break;
            }
        }
        if (!$signatureFound) {
            throw Exception\SignatureVerificationException::factory(
                'No signatures found matching the expected signature for payload',
                $payload,
                $header
            );
        }

        
        if (($tolerance > 0) && (\abs(\time() - $timestamp) > $tolerance)) {
            throw Exception\SignatureVerificationException::factory(
                'Timestamp outside the tolerance zone',
                $payload,
                $header
            );
        }

        return true;
    }

    
    private static function getTimestamp($header)
    {
        $items = \explode(',', $header);

        foreach ($items as $item) {
            $itemParts = \explode('=', $item, 2);
            if ('t' === $itemParts[0]) {
                if (!\is_numeric($itemParts[1])) {
                    return -1;
                }

                return (int) $itemParts[1];
            }
        }

        return -1;
    }

    
    private static function getSignatures($header, $scheme)
    {
        $signatures = [];
        $items = \explode(',', $header);

        foreach ($items as $item) {
            $itemParts = \explode('=', $item, 2);
            if (\trim($itemParts[0]) === $scheme) {
                $signatures[] = $itemParts[1];
            }
        }

        return $signatures;
    }

    
    private static function computeSignature($payload, $secret)
    {
        return \hash_hmac('sha256', $payload, $secret);
    }
}
