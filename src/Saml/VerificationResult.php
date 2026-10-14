<?php

declare(strict_types=1);

namespace Signclave\Saml;

if (!defined('ABSPATH')) {
    exit;
}

use DOMDocument;

/**
 * Result of XML-DSig verification against the pinned IdP certificate.
 */
final readonly class VerificationResult
{
    /**
     * @param bool          $ok             Whether verification succeeded.
     * @param DOMDocument|null $dom       Parsed signed document on success.
     * @param string        $signedId       ID attribute of the signed element.
     * @param string        $signedNodeName Local name of the signed element.
     * @param string        $error          Error message on failure.
     */
    private function __construct(
        public bool $ok,
        public ?DOMDocument $dom,
        public string $signedId,
        public string $signedNodeName,
        public string $error
    ) {
    }

    /**
     * Create a successful verification result.
     *
     * @param DOMDocument $dom            Parsed signed document.
     * @param string      $signedId       ID attribute of the signed element.
     * @param string      $signedNodeName Local name of the signed element.
     */
    public static function success(DOMDocument $dom, string $signedId, string $signedNodeName): self
    {
        return new self(true, $dom, $signedId, $signedNodeName, '');
    }

    /**
     * Create a failed verification result.
     *
     * @param string $error Human-readable verification error.
     */
    public static function failure(string $error): self
    {
        return new self(false, null, '', '', $error);
    }
}
