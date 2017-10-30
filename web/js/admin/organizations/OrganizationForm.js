import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField, FileField } from 'ui/Field';
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
}) {
  return (
    <div>
      <header>{org.id ? `Edit Org - ${org.id}` : 'Add an Organization'}</header>
      <Form onSubmit={org.onSubmit}>
        <input
          type="hidden"
          name="id"
          defaultValue={org.id}
          style={{ display: 'none' }}
        />
        <Field
          label="Email"
          name="email"
          required
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
        <Field label="Hostname" />
        <br />
        <SelectField label="Auth Type" defaultValue="password" name="auth_type">
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
          defaultValue={org.allow_sign_up}
          name="allow_sign_up"
        />
        <br />
        {org.id ? null : (
          <div>
            <Field label="Full Name" name="admin.name" />
            <Field label="Employee Code" name="admin.username" />
            <Field label="Password" type="password" name="admin.password" />
            <Field
              label="Re-Type password"
              type="password"
              name="admin.password_confirmation"
            />
            <Field label="Employee Code" name="admin.employee_code" />
            <br />
            <Field label="Department Code" name="admin.department_code" />
            <Field label="Branch Code" name="admin.branch_code" />
            <Field label="Location Code" name="admin.location_code" />
            <Field label="Supervisor Code" name="admin.supervisor_code" />
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
        <AsyncButton
          text="Save"
          class="btn"
          type="submit"
          pendingClass="small spinner"
          onSubmit={handleSave}
        />
      </Form>
    </div>
  );
}
