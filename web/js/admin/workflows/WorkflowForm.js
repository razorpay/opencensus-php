import React, { Component } from 'react';

import Form from 'ui/Form';
import Table from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';
import Field, { SelectField } from 'ui/Field';

export default class WorkflowForm extends Component {
  permFields = item => {
    let { onDeletePerms } = this.props;
    return [
      ['Name', item => item.name],
      [
        'Action',
        item => (
          <div class="link danger" onClick={e => onDeletePerms(item)}>
            Remove
          </div>
        ),
      ],
    ];
  };

  render() {
    let {
      id,
      name,
      allPerms,
      permissions,
      onSelectPerms,
      onLevelAdd,
      onSubmit,
      children,
    } = this.props;

    return (
      <div class="workflow-container box">
        <Form onSubmit={onSubmit}>
          <header>
            {id ? `Edit - ${id}` : 'Create Workflow'}
            <div class="btn" onClick={onLevelAdd}>
              + Add a Step
            </div>
            <AsyncButton
              text="Save Changes"
              class="btn"
              pendingClass="small spinner"
              onSubmit={onSubmit}
            />
          </header>
          <div class="split">
            <Field label="Workflow Name" name="name" defaultValue={name} />
            <SelectField label="Actions List" onChange={onSelectPerms} value="">
              <option value="" />
              {allPerms.map(perm => (
                <option value={perm.id} key={perm.id}>
                  {perm.name}
                </option>
              ))}
            </SelectField>
            {permissions.length ? (
              <Table
                items={permissions}
                fields={this.permFields()}
                bordered={true}
              />
            ) : null}
          </div>
          <div class="split">{children && children.map(child => child)}</div>
        </Form>
      </div>
    );
  }
}
