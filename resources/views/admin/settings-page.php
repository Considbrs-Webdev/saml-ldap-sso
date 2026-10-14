<?php
/**
 * Plugin settings screen markup.
 *
 * Variables set by SettingsPage::renderPage().
 *
 * @package SAML_LDAP_SSO
 * @since   0.1.4
 *
 * @var array  $options
 * @var array  $roles
 * @var string $bind_user_value
 * @var string $metadata_xml
 * @var string $metadata_url
 * @var string $metadata_download
 * @var array  $recent_acs_failures
 * @var string $mappings_text
 * @var string $bypass_url
 * @var string $acs_url
 * @var string $secondary_cert
 * @var bool   $allow_idp_initiated
 * @var int    $clock_skew_seconds
 * @var bool   $role_downgrade
 * @var string $protected_logins
 * @var string $role_priority
 * @var int    $log_retention_days
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template locals from View::render().

$code_ok = array( 'code' => array() );
?>
<div class="wrap sls-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php settings_errors( 'sls_settings' ); ?>
	<?php if ( ! empty( $recent_acs_failures ) ) : ?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Recent SAML login rejections (ACS)', 'saml-ldap-sso' ); ?></strong></p>
			<p class="description"><?php esc_html_e( 'The SSO diagnostic test only validates the outbound AuthnRequest. These lines show why inbound SAML responses were rejected.', 'saml-ldap-sso' ); ?></p>
			<pre class="sls-log sls-log--compact"><?php echo esc_html( implode( "\n", $recent_acs_failures ) ); ?></pre>
		</div>
	<?php endif; ?>
	<form method="post" action="options.php">
		<?php settings_fields( 'sls' ); ?>
		<div class="sls-section">
			<h2><?php esc_html_e( 'General', 'saml-ldap-sso' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Automatic SSO', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[general][auto_sso]" value="1" <?php checked( ! empty( $options['general']['auto_sso'] ) ); ?>>
							<?php echo wp_kses( __( 'Redirect visits to <code>wp-login.php</code> to SAML', 'saml-ldap-sso' ), $code_ok ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'When on, visits go to the IdP. Local password login uses the emergency URL under Security. When off, the form stays visible and a Log in with SSO button is shown below the form.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Default role', 'saml-ldap-sso' ); ?></th>
					<td>
						<select name="sls_options[general][default_role]">
							<?php foreach ( $roles as $role_key => $role_data ) : ?>
								<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $options['general']['default_role'], $role_key ); ?>><?php echo esc_html( $role_data['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'No group match', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[general][deny_if_no_group_match]" value="1" <?php checked( ! empty( $options['general']['deny_if_no_group_match'] ) ); ?>>
							<?php esc_html_e( 'Deny login when mappings exist and no group matches', 'saml-ldap-sso' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Role downgrade', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[general][role_downgrade]" value="1" <?php checked( $role_downgrade ); ?>>
							<?php esc_html_e( 'Remove mapped roles when the user no longer matches the group', 'saml-ldap-sso' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Only roles granted by this plugin are removed. The last remaining administrator is never downgraded.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Protected logins', 'saml-ldap-sso' ); ?></th>
					<td>
						<textarea name="sls_options[general][protected_logins]" rows="4" class="large-text code"><?php echo esc_textarea( $protected_logins ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One WordPress username per line. These accounts never lose the administrator role through group mapping.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Role priority', 'saml-ldap-sso' ); ?></th>
					<td>
						<input type="text" class="large-text code" name="sls_options[general][role_priority]" value="<?php echo esc_attr( $role_priority ); ?>">
						<p class="description"><?php esc_html_e( 'Comma-separated role slugs, most privileged first. Used by the “highest role” strategy. Roles not listed rank below these.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="sls-section">
			<h2><?php esc_html_e( 'Security', 'saml-ldap-sso' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Strict SAML validation', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[general][saml_strict_validation]" value="1" <?php checked( ! empty( $options['general']['saml_strict_validation'] ) ); ?>>
							<?php esc_html_e( 'Require IdP entity ID, audience, destination, recipient, and matching AuthnRequest ID', 'saml-ldap-sso' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Recommended. Disabling weakens assertion binding (not recommended in production).', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'IdP-initiated SSO', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[general][allow_idp_initiated]" value="1" <?php checked( $allow_idp_initiated ); ?>>
							<?php echo wp_kses( __( 'Accept unsolicited responses without <code>InResponseTo</code>', 'saml-ldap-sso' ), $code_ok ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Off by default. Enabling this drops the binding to an AuthnRequest we sent, so replay protection relies on the Recipient and response ID only. Enable only if the IdP starts the login.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Clock skew tolerance', 'saml-ldap-sso' ); ?></th>
					<td>
						<input type="number" class="small-text" name="sls_options[general][saml_clock_skew_seconds]" min="0" max="300" step="1" value="<?php echo esc_attr( (string) $clock_skew_seconds ); ?>">
						<?php esc_html_e( 'seconds', 'saml-ldap-sso' ); ?>
						<p class="description"><?php esc_html_e( 'Allowed time drift between the IdP and this server when validating assertion validity windows. Default 60, maximum 300.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Trust proxy HTTPS headers', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[general][trust_proxy_headers]" value="1" <?php checked( ! empty( $options['general']['trust_proxy_headers'] ) ); ?>>
							<?php echo wp_kses( __( 'Honor <code>X-Forwarded-Proto</code> / <code>X-Forwarded-SSL</code>', 'saml-ldap-sso' ), $code_ok ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Enable only behind a trusted reverse proxy that terminates TLS.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Link accounts by email', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[general][allow_email_account_linking]" value="1" <?php checked( ! empty( $options['general']['allow_email_account_linking'] ) ); ?>>
							<?php esc_html_e( 'Allow LDAP password login to match existing WordPress users by email', 'saml-ldap-sso' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Off by default (OWASP). Applies to the LDAP password path only. SAML may bind to an existing user_login on first login; afterwards identity is the stored IdP NameID, never email.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Emergency local login', 'saml-ldap-sso' ); ?></th>
					<td>
						<?php if ( '' !== $bypass_url ) : ?>
							<p><code><?php echo esc_html( $bypass_url ); ?></code></p>
							<p class="description"><?php esc_html_e( 'Secret URL for local WordPress accounts only. SAML/LDAP users cannot use it. Store securely.', 'saml-ldap-sso' ); ?></p>
						<?php else : ?>
							<p class="description"><?php esc_html_e( 'No bypass key configured. Save settings or regenerate a key below.', 'saml-ldap-sso' ); ?></p>
						<?php endif; ?>
						<button type="submit" class="button button-primary" name="sls_regenerate_bypass_key" value="1"><?php esc_html_e( 'Regenerate emergency login key', 'saml-ldap-sso' ); ?></button>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Log retention', 'saml-ldap-sso' ); ?></th>
					<td>
						<input type="number" class="small-text" name="sls_options[general][log_retention_days]" min="7" max="730" step="1" value="<?php echo esc_attr( (string) $log_retention_days ); ?>">
						<?php esc_html_e( 'days', 'saml-ldap-sso' ); ?>
						<p class="description"><?php esc_html_e( 'Audit and diagnostic log lines older than this are deleted daily. Allowed range: 7–730 days (default 90).', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="sls-section">
			<h2><?php esc_html_e( 'SAML', 'saml-ldap-sso' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'SP Entity ID', 'saml-ldap-sso' ); ?></th>
					<td><input type="url" class="regular-text" name="sls_options[saml][sp_entity_id]" value="<?php echo esc_attr( $options['saml']['sp_entity_id'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'ACS URL', 'saml-ldap-sso' ); ?></th>
					<td><code><?php echo esc_html( $acs_url ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Metadata URL', 'saml-ldap-sso' ); ?></th>
					<td><code><?php echo esc_html( $metadata_url ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'SP metadata', 'saml-ldap-sso' ); ?></th>
					<td>
						<p class="description"><?php echo wp_kses( __( 'Import this file in F5 under <strong>SAML Identity Provider → SAML SP Connectors → From Metadata</strong>. Set signing certificate to <strong>None</strong>.', 'saml-ldap-sso' ), array( 'strong' => array() ) ); ?></p>
						<p class="sls-metadata-actions">
							<a class="button button-secondary" href="<?php echo esc_url( $metadata_download ); ?>"><?php esc_html_e( 'Download metadata XML', 'saml-ldap-sso' ); ?></a>
							<button type="button" class="button button-primary" id="sls-copy-metadata"><?php esc_html_e( 'Copy metadata', 'saml-ldap-sso' ); ?></button>
						</p>
						<textarea id="sls-metadata-preview" rows="12" class="large-text code sls-log sls-log--metadata" readonly><?php echo esc_textarea( $metadata_xml ); ?></textarea>
					</td>
				</tr>
			</table>
			<h3><?php esc_html_e( 'Identity Provider', 'saml-ldap-sso' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Entity ID', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="regular-text" name="sls_options[saml][primary_entity_id]" value="<?php echo esc_attr( $options['saml']['primary_entity_id'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'SSO URL', 'saml-ldap-sso' ); ?></th>
					<td>
						<input type="url" class="regular-text" name="sls_options[saml][primary_sso_url]" value="<?php echo esc_attr( $options['saml']['primary_sso_url'] ); ?>">
						<p class="description"><?php echo wp_kses( __( 'Use <code>SingleSignOnService</code> from IdP metadata, not the <code>.xml</code> metadata file URL.', 'saml-ldap-sso' ), $code_ok ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Group attribute', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="regular-text" name="sls_options[saml][primary_group_attr]" value="<?php echo esc_attr( $options['saml']['primary_group_attr'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Username attribute', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="regular-text" name="sls_options[saml][primary_username_attr]" value="<?php echo esc_attr( $options['saml']['primary_username_attr'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Email attribute', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="regular-text" name="sls_options[saml][primary_email_attr]" value="<?php echo esc_attr( $options['saml']['primary_email_attr'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'First name attribute', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="regular-text" name="sls_options[saml][primary_first_name_attr]" value="<?php echo esc_attr( $options['saml']['primary_first_name_attr'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last name attribute', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="regular-text" name="sls_options[saml][primary_last_name_attr]" value="<?php echo esc_attr( $options['saml']['primary_last_name_attr'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'X509 certificate (PEM)', 'saml-ldap-sso' ); ?></th>
					<td><textarea name="sls_options[saml][primary_x509_cert]" rows="7" class="large-text code"><?php echo esc_textarea( $options['saml']['primary_x509_cert'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Secondary X509 certificate (PEM)', 'saml-ldap-sso' ); ?></th>
					<td>
						<textarea name="sls_options[saml][secondary_x509_cert]" rows="7" class="large-text code"><?php echo esc_textarea( $secondary_cert ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Optional. Assertions signed with either certificate are accepted, so the IdP certificate can be rotated without downtime. Add the new certificate here, wait for the IdP to switch, then move it to the field above and clear this one.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="sls-section">
			<h2><?php esc_html_e( 'LDAP', 'saml-ldap-sso' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'LDAP fallback', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[ldap][enabled]" value="1" <?php checked( ! empty( $options['ldap']['enabled'] ) ); ?>>
							<?php esc_html_e( 'Enable LDAP fallback authentication', 'saml-ldap-sso' ); ?>
						</label>
						<p class="description"><?php echo wp_kses( __( 'When enabled and fully configured, LDAP is used for password login and to verify users before WordPress creates a <strong>new</strong> account from SAML. Existing WordPress accounts can still sign in via SAML if the directory is unavailable. When disabled (or incomplete), SAML runs alone and can create users from IdP attributes.', 'saml-ldap-sso' ), array( 'strong' => array() ) ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Directory type', 'saml-ldap-sso' ); ?></th>
					<td>
						<select name="sls_options[ldap][directory_type]">
							<option value="edir" <?php selected( $options['ldap']['directory_type'], 'edir' ); ?>><?php esc_html_e( 'eDirectory', 'saml-ldap-sso' ); ?></option>
							<option value="ad" <?php selected( $options['ldap']['directory_type'], 'ad' ); ?>><?php esc_html_e( 'Active Directory', 'saml-ldap-sso' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Hosts', 'saml-ldap-sso' ); ?></th>
					<td>
						<input type="text" class="large-text" name="sls_options[ldap][hosts]" value="<?php echo esc_attr( $options['ldap']['hosts'] ); ?>">
						<p class="description"><?php echo wp_kses( __( 'Comma-separated. Use <code>ldaps://host:636</code> in production.', 'saml-ldap-sso' ), $code_ok ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Base DN', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="large-text" name="sls_options[ldap][base_dn]" value="<?php echo esc_attr( $options['ldap']['base_dn'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Bind user / Bind DN', 'saml-ldap-sso' ); ?></th>
					<td>
						<input type="text" class="large-text" name="sls_options[ldap][bind_user]" value="<?php echo esc_attr( $bind_user_value ); ?>">
						<p class="description"><?php esc_html_e( 'The service account used for LDAP reads. Can be DN, UPN, or account name depending on your directory.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Bind password', 'saml-ldap-sso' ); ?></th>
					<td>
						<input type="password" class="regular-text" name="sls_bind_password" value="">
						<p class="description"><?php esc_html_e( 'Leave empty to keep existing secret.', 'saml-ldap-sso' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Security override', 'saml-ldap-sso' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sls_options[ldap][allow_insecure_ldap]" value="1" <?php checked( ! empty( $options['ldap']['allow_insecure_ldap'] ) ); ?>>
							<?php echo wp_kses( __( 'Allow <code>ldap://</code> (not recommended)', 'saml-ldap-sso' ), $code_ok ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'User filter', 'saml-ldap-sso' ); ?></th>
					<td>
						<input type="text" class="large-text code" id="sls-user-filter" name="sls_options[ldap][user_filter]" value="<?php echo esc_attr( (string) ( $options['ldap']['user_filter'] ?? '' ) ); ?>">
						<p class="description" id="sls-user-filter-help">
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: %s code token, 2: typical eDirectory filter, 3: typical Active Directory filter */
									__( 'Use %1$s as username placeholder. Typical eDirectory: %2$s Typical AD: %3$s', 'saml-ldap-sso' ),
									'<code>%s</code>',
									'<code>(&amp;(objectClass=inetOrgPerson)(cn=%s))</code>',
									'<code>(&amp;(objectClass=user)(sAMAccountName=%s))</code>'
								),
								$code_ok
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Group attribute (eDirectory)', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="regular-text" name="sls_options[ldap][group_attr_edir]" value="<?php echo esc_attr( $options['ldap']['group_attr_edir'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Group attribute (AD)', 'saml-ldap-sso' ); ?></th>
					<td><input type="text" class="regular-text" name="sls_options[ldap][group_attr_ad]" value="<?php echo esc_attr( $options['ldap']['group_attr_ad'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Connection test', 'saml-ldap-sso' ); ?></th>
					<td>
						<button type="button" class="button button-primary" id="sls-test-ldap-quick"><?php esc_html_e( 'Test LDAP', 'saml-ldap-sso' ); ?></button>
						<p class="description"><?php esc_html_e( 'Quick service bind validation for current LDAP settings.', 'saml-ldap-sso' ); ?></p>
						<textarea id="sls-ldap-quick-log" rows="7" class="large-text code sls-log sls-log--compact" readonly></textarea>
					</td>
				</tr>
			</table>
		</div>

		<div class="sls-section">
			<h2><?php esc_html_e( 'Group to role mappings', 'saml-ldap-sso' ); ?></h2>
			<p class="description"><?php echo wp_kses( __( 'One row per mapping in format: <code>source|group|role</code>. Example: <code>ldap|cn=wp-admins,ou=groups,o=org|administrator</code>', 'saml-ldap-sso' ), $code_ok ); ?></p>
			<textarea name="sls_role_mappings" rows="8" class="large-text code"><?php echo esc_textarea( $mappings_text ); ?></textarea>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'saml-ldap-sso' ); ?></button>
		</p>
	</form>

	<div class="sls-section">
		<h2><?php esc_html_e( 'Diagnostics', 'saml-ldap-sso' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Use this for technical validation. Password is never written to logs.', 'saml-ldap-sso' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Test username', 'saml-ldap-sso' ); ?></th>
				<td><input type="text" class="regular-text" id="sls_test_username" autocomplete="off"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Test password', 'saml-ldap-sso' ); ?></th>
				<td><input type="password" class="regular-text" id="sls_test_password" autocomplete="new-password"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Run tests', 'saml-ldap-sso' ); ?></th>
				<td>
					<button type="button" class="button button-primary" id="sls-test-ldap-auth"><?php esc_html_e( 'Test LDAP auth', 'saml-ldap-sso' ); ?></button>
					<button type="button" class="button button-primary" id="sls-test-sso"><?php esc_html_e( 'Test SSO flow', 'saml-ldap-sso' ); ?></button>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Technical log', 'saml-ldap-sso' ); ?></th>
				<td>
					<textarea id="sls-tech-log" rows="14" class="large-text code sls-log sls-log--tech" readonly></textarea>
				</td>
			</tr>
		</table>
	</div>
</div>
