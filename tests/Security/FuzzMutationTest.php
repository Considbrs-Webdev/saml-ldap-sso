<?php

declare(strict_types=1);

use SamlLdapSso\Saml\SignatureVerifier;

it('never accepts a mutated assertion as a different valid payload', function () {
    $original = (string) file_get_contents(dirname(__DIR__) . '/fixtures/saml/valid-assertion.xml');
    $cert = (string) file_get_contents(dirname(__DIR__) . '/fixtures/saml/idp.crt');
    $verifier = new SignatureVerifier();
    $seed = 1337;
    mt_srand($seed);
    $rounds = 64;
    $checked = 0;

    for ($i = 0; $i < $rounds; $i++) {
        $mutated = $original;
        $len = strlen($mutated);
        $changes = mt_rand(1, 8);
        for ($c = 0; $c < $changes; $c++) {
            $pos = mt_rand(0, $len - 1);
            $mutated[$pos] = chr(mt_rand(0, 255));
        }
        if ($mutated === $original) {
            continue;
        }
        $result = $verifier->verifySignedXml($mutated, $cert);
        expect($result->ok)->toBeFalse("Fuzz seed {$seed} round {$i} accepted altered XML");
        $checked++;
    }

    expect($checked)->toBeGreaterThan(0);
});
