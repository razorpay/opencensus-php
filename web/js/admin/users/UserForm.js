import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Table from 'ui/Table';

@observer
export default class UserForm extends Component {
  groupFields = () => {
    let { groups, allGroups, selectAllGroups, toggleGroup } = this.props;

    let isAllChecked = groups.size === allGroups.size;

    return [
      [
        <input
          type="checkbox"
          checked={isAllChecked}
          onChange={selectAllGroups}
        />,
        item => (
          <input
            type="checkbox"
            checked={groups.has(item.id)}
            onChange={e => toggleGroup(item.id)}
          />
        ),
      ],
      ['Group', item => item.name],
      ['Description', item => item.description],
    ];
  };

  /*
  * Render input fields according to fieldMaps received.
  */
  render() {
    let {
      fields,
      user = {},
      roles,
      allGroups,
      allRoles,
      updateRole,
      onSubmit,
    } = this.props;

    //convert map into array of [[key, value], [key, value], ... ] for rendering of table/select option
    roles = roles.entries();
    allGroups = allGroups.entries();
    allRoles = allRoles.entries();

    return (
      <div class="box user-form-container">
        <header>{user.id ? `Edit User-${user.id}` : 'Add an User'} </header>
        <Form onSubmit={onSubmit}>
          {fields.indexOf('name') > -1 && (
            <Field
              name="name"
              label="Full Name"
              required
              defaultValue={user.name}
            />
          )}

          {fields.indexOf('username') > -1 && !user.id ? (
            <Field
              name="name"
              label="Username"
              required
              defaultValue={user.username}
            />
          ) : null}

          {fields.indexOf('email') > -1 && !user.id ? (
            <Field
              name="email"
              label="Email"
              required
              defaultValue={user.email}
            />
          ) : null}

          {fields.indexOf('password') > -1 && !user.id ? (
            <Field name="password" label="Password" required />
          ) : null}

          {fields.indexOf('password_confirmation') > -1 && !user.id ? (
            <Field
              name="password_confirmation"
              label="Re enter Password"
              required
            />
          ) : null}

          {fields.indexOf('employee_code') > -1 && (
            <Field
              name="employee_code"
              label="Employee Code"
              required
              defaultValue={user.employee_code}
            />
          )}

          {fields.indexOf('department_code') > -1 && (
            <Field
              name="department_code"
              label="Department Code"
              required
              defaultValue={user.department_code}
            />
          )}

          {fields.indexOf('branch_code') > -1 && (
            <Field
              name="branch_code"
              label="Branch Code"
              required
              defaultValue={user.branch_code}
            />
          )}

          {fields.indexOf('location_code') > -1 && (
            <Field
              name="location_code"
              label="Location Code"
              required
              defaultValue={user.location_code}
            />
          )}

          {fields.indexOf('supervisor_code') > -1 && (
            <Field
              name="supervisor_code"
              label="Supervisor Code"
              required
              defaultValue={user.supervisor_code}
            />
          )}

          {fields.indexOf('allow_all_merchants') > -1 && (
            <CheckField
              name="allow_all_merchants"
              label="Allow All Merchants"
              required
              defaultChecked={user.allow_all_merchants}
            />
          )}

          {fields.indexOf('disabled') > -1 && (
            <CheckField
              name="disabled"
              label="Disabled"
              required
              defaultChecked={user.disabled}
            />
          )}

          <br />

          {fields.indexOf('roles') > -1 && (
            <div>
              <SelectField
                label="Roles"
                onChange={e => updateRole(e.target.value)}
              >
                <option value="" />
                {allRoles.map(role => (
                  <option key={role[0]} value={role[0]}>
                    {role[1]}
                  </option>
                ))}
              </SelectField>
              {roles.map(r => (
                <span
                  class="link"
                  key={r[0]}
                  onClick={e => updateRole(r[0], true)}
                >
                  {r[1]}
                  {' x'}
                </span>
              ))}
            </div>
          )}

          {fields.indexOf('groups') > -1 && [
            <header key="1">Groups</header>,
            <Table
              key="2"
              items={allGroups.map(g => g[1])}
              fields={this.groupFields()}
              border={true}
            />,
          ]}
          <div class="user-form-btn">
            <button>Save</button>
          </div>
        </Form>
      </div>
    );
  }
}
