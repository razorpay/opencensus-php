import React from 'react';

import Form from 'ui/Form';
import Field, { SelectField, CheckField, FileField } from 'ui/Field';

export default function OrgForm({
  id,
  business_name,
  display_name,
  email,
  email_domains,
  custom_code,
  from_email,
  signature_email,
  invoice_logo_url,
  login_logo_url,
  main_logo_url,
  allow_sign_up,
  onSubmit,
  onEditPerms,
  onSave,
}) {
  return (
    <div>
      <header>{id ? `Edit Org - ${id}` : 'Add an Organization'}</header>
      <Form onSubmit={onSubmit}>
        <input
          type="hidden"
          name="id"
          defaultValue={id}
          style={{ display: 'none' }}
        />
        <Field label="Email" name="email" required defaultValue={email} />
        <Field
          label="Business Name"
          name="business_name"
          required
          defaultValue={business_name}
        />
        <Field
          label="Display Name"
          name="display_name"
          required
          defaultValue={display_name}
        />
        <br />
        <Field
          label="Email Domains"
          name="email_domains"
          required
          defaultValue={email_domains}
        />
        <Field label="Hostname" />
        <SelectField label="Auth Type" name="auth_type">
          <option value="">Please select an auth type</option>
          <option value="password">Password</option>
          <option value="google_auth">Google Auth</option>
        </SelectField>
        <br />
        <Field
          label="Custom Code"
          name="custom_code"
          defaultValue={custom_code}
        />
        <Field label="From Email" name="from_email" defaultValue={from_email} />
        <Field
          label="Signature Email"
          name="signature_email"
          defaultValue={signature_email}
        />
        <br />
        {id ? (
          <div>
            <FileField
              label="Login Logo"
              name="login_logo_url"
              accept="image/jpeg,image/jpg,image/png"
            />
            <FileField
              label="Invoice Logo"
              name="invoice_logo_url"
              accept="image/jpeg,image/jpg,image/png"
            />
            <FileField
              label="Main Logo"
              name="main_logo_url"
              accept="image/jpeg,image/jpg,image/png"
            />
          </div>
        ) : (
          ''
        )}

        <br />
        <div class="link" onClick={onEditPerms}>
          Edit Permissions
        </div>
        <br />
        <CheckField
          label="Allow Sign Up"
          defaultValue={allow_sign_up}
          name="allow_sign_up"
        />
        <br />
        <button type="submit" onClick={onSave}>
          Save
        </button>
      </Form>
    </div>
  );
}
