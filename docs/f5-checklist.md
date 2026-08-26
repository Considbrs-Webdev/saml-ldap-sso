# F5 APM staging checklist

Use this against a **staging** IdP and directory. Do not paste production hostnames, bind DNs, or certificates into git.

A captured, anonymized F5 `SAMLResponse` is **not** in the repo yet. Unit/security tests use synthetic XML with the confirmed attribute names and NameID format. Add a sanitized fixture under `tests/fixtures/saml/` (generic filename, re-signed with the test key) once a response has been captured.

## Typical F5 + eDirectory profile (plugin defaults)

These match the plugin defaults. They do **not** need to be re-discovered on staging, only re-checked if the IdP connector changes.

| Item | Confirmed value |
| --- | --- |
| SAML attributes | `uid`, `mail`, `givenName`, `sn`, `groups` |
| Assertion signing | `WantAssertionsSigned=true`; unsigned assertions are rejected |
| NameID format | **unspecified** is accepted when the value is stable (for example a directory uid). Transient is still rejected. |
| Directory | eDirectory; user filter `(&(objectClass=inetOrgPerson)(cn=%s))`; group attribute `groupMembership` |
| LDAP transport | `ldaps://` on port 636; `ldap://` without “allow insecure” is fail-closed |
| SSO binding | HTTP-POST to the IdP SSO URL (plugin AuthnRequest) |

## Still requires staging

1. SP metadata imported in F5 APM without warnings.
2. IdP Entity ID, SSO URL, and signing certificate pasted into the plugin (staging values only).
3. Login as a test user on staging WordPress: new WordPress user created with the mapped role.
4. Login as an existing WordPress user: no duplicate account.
5. Email changed in the directory: same WordPress user, no new account.
6. IdP-initiated SSO: works when the setting is on, rejected when off.
7. Clock skew: compare F5 and web-server clocks; raise **Clock skew** (max 300 s) only if needed.
8. Certificate rotation with primary + secondary X.509 configured, no outage.
9. LDAP down: existing user can sign in via SAML; a new user is rejected (`LdapVerificationFailed`).
10. Emergency local login while SSO is on.
11. `wp sls sso disable` — verify before it is needed in an incident.
12. Wrong IdP certificate → login rejected, audit log shows the reason.
13. Reverse proxy: HTTPS detection correct with and without **Trust proxy HTTPS headers**.
14. Capture one sanitized F5 `SAMLResponse`, re-sign with the test key, and check it in as a fixture.
