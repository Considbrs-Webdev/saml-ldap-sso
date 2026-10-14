<?php

declare(strict_types=1);

use Signclave\Vendor\RobRichards\XMLSecLibs\XMLSecurityDSig;

it('does not autoload unprefixed xmlseclibs', function () {
    expect(class_exists('RobRichards\\XMLSecLibs\\XMLSecurityDSig', true))->toBeFalse()
        ->and(class_exists(XMLSecurityDSig::class, true))->toBeTrue();
});
