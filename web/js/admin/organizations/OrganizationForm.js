import React from 'react';
import Form from 'ui/Form';
import Field, { FileField, SelectField, CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import PermissionsList from './PermissionsList';

export default function OrgForm({
  org,
  permissions,
  selectedPerms,
  workflowPerms,
  handleAllSelect,
  handlePermissionSelect,
  handleWorkflowPermissionSelect,
  handleSave,
  onFileUpload,
  filesURL,
}) {
  return (
    <div class="box">
      <header>{org.id ? `Edit Org - ${org.id}` : 'Add an Organization'}</header>
      <Form onSubmit={handleSave}>
        <div class="orgs-form-container">
          <header>Orgs Details:</header>
          <input
            type="hidden"
            name="id"
            defaultValue={org.id}
            style={{ display: 'none' }}
            required
          />
          <Field
            label="Email"
            name="email"
            defaultValue={org.email}
            type="email"
            required
          />
          <Field
            label="Business Name"
            name="business_name"
            required
            defaultValue={org.business_name}
          />
          <Field
            label="Display Name"
            name="display_name"
            required
            defaultValue={org.display_name}
          />
          <Field
            label="Email Domains"
            name="email_domains"
            required
            defaultValue={org.email_domains}
          />
          <Field label="Hostname" name="hostname" defaultValue={org.hostname} />

          <SelectField
            label="Auth Type"
            defaultValue={org.auth_type || 'password'}
            name="auth_type"
          >
            <option value="">Please select an auth type</option>
            <option value="password">Password</option>
            <option value="google_auth">Google Auth</option>
          </SelectField>

          <Field
            label="Custom Code"
            name="custom_code"
            defaultValue={org.custom_code}
          />
          <Field
            label="From Email"
            name="from_email"
            type="email"
            required
            defaultValue={org.from_email}
          />
          <Field
            label="Signature Email"
            name="signature_email"
            type="email"
            required
            defaultValue={org.signature_email}
          />
          <CheckField
            label="Allow Sign Up"
            defaultChecked={org.allow_sign_up | 0}
            name="allow_sign_up"
          />

          {org.id ? (
            <div class="logo-container">
              <header>Upload/Select Logo:</header>
              <div class="orgs-logo">
                {filesURL.login_logo_url && (
                  <img src={filesURL.login_logo_url} width="75" height="75" />
                )}
                <FileField
                  name="login_logo_url"
                  label="Login Logo"
                  class="orgs-file"
                  accept="image/jpeg,image/jpg,image/png"
                  onChange={e =>
                    onFileUpload(e.target.files[0], 'login_logo', 'login')
                  }
                />
              </div>
              <div class="orgs-logo">
                {filesURL.main_logo_url && (
                  <img src={filesURL.main_logo_url} width="75" height="75" />
                )}
                <FileField
                  name="main_logo_url"
                  label="Main (Dashboard) Logo"
                  class="orgs-file"
                  accept="image/jpeg,image/jpg,image/png"
                  onChange={e =>
                    onFileUpload(e.target.files[0], 'main_logo', 'main')
                  }
                />
              </div>
              <div class="orgs-logo">
                {filesURL.invoice_logo_url && (
                  <img src={filesURL.invoice_logo_url} width="75" height="75" />
                )}
                <FileField
                  name="invoice_logo_url"
                  label="Invoice Logo"
                  class="orgs-file"
                  accept="image/jpeg,image/jpg,image/png"
                  onChange={e =>
                    onFileUpload(e.target.files[0], 'invoice_logo', 'invoice')
                  }
                />
              </div>
            </div>
          ) : (
            <div class="org-admin">
              <header>Admin Details:</header>
              <Field label="Admin Name" name="admin[name]" required />
              <Field label="User Name" name="admin[username]" required />
              <Field
                label="Password"
                type="password"
                name="admin[password]"
                required
              />
              <Field
                label="Re-Type password"
                type="password"
                name="admin[password_confirmation]"
                required
              />
              <input
                type="hidden"
                label="Employee Code"
                name="admin[employee_code]"
                value="E001"
                required
              />
              <input
                type="hidden"
                label="Department Code"
                name="admin[department_code]"
                value="D001"
                required
              />
              <input
                type="hidden"
                label="Branch Code"
                name="admin[branch_code]"
                value="B001"
                required
              />
              <input
                type="hidden"
                label="Location Code"
                name="admin[location_code]"
                value="L001"
                required
              />
              <input
                type="hidden"
                label="Supervisor Code"
                name="admin[supervisor_code]"
                value="S001"
                required
              />
            </div>
          )}
          <PermissionsList
            permissions={permissions}
            workflowPerms={workflowPerms}
            selectedPerms={selectedPerms}
            onPermissionSelect={handlePermissionSelect}
            onWorkflowPermissionSelect={handleWorkflowPermissionSelect}
            onAllSelect={handleAllSelect}
          />
        </div>
        <div class="orgs-form-btn">
          <AsyncButton
            text="Save"
            class="btn"
            pendingClass="small spinner"
            onSubmit={handleSave}
          />
        </div>
      </Form>
    </div>
  );
}
