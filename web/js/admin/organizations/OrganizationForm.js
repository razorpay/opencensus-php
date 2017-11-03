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
      <Form onSubmit={handleSave}>
        <div class="orgs-form-container">
          <header>
            {org.id ? `Edit Org - ${org.id}` : 'Add an Organization'}
          </header>
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

          {org.id
            ? null
            : [
                <Field label="Full Name" name="admin.name" key="name" />,
                <Field label="Employee Code" name="admin.username" key="1" />,
                <Field
                  label="Password"
                  type="password"
                  name="admin.password"
                  key="2"
                />,
                <Field
                  label="Re-Type password"
                  type="password"
                  name="admin.password_confirmation"
                  key="3"
                />,
                <Field
                  label="Employee Code"
                  name="admin.employee_code"
                  key="4"
                />,
                <Field
                  label="Department Code"
                  name="admin.department_code"
                  key="5"
                />,
                <Field label="Branch Code" name="admin.branch_code" key="6" />,
                <Field
                  label="Location Code"
                  name="admin.location_code"
                  key="7"
                />,
                <Field
                  label="Supervisor Code"
                  name="admin.supervisor_code"
                  key="8"
                />,
              ]}
          <CheckField
            label="Allow Sign Up"
            defaultValue={org.allow_sign_up}
            name="allow_sign_up"
          />
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
