import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import Table from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';

export default class RolesForm extends Component {
  fields = item => {
    let { onSelect, onSelectAll, selectedPerms } = this.props;
    return [
      [
        <input type="checkbox" onChange={onSelectAll} />,
        item => (
          <input
            type="checkbox"
            onChange={e => onSelect(e, item.id)}
            checked={!!selectedPerms[item.id]}
          />
        ),
      ],
      ['Permissions', item => item.name],
      ['Category', item => item.category],
      ['Description', item => item.description],
    ];
  };

  render() {
    let { name, description, allPerms, onSubmit } = this.props;
    return (
      <div class="roles-form-container">
        <header>Edit Role</header>
        <Form onSubmit={onSubmit}>
          <Field name="name" label="Name" required defaultValue={name} />
          <Field
            name="description"
            label="Description"
            required
            defaultValue={description}
          />
          <header>Permissions</header>
          <Table items={allPerms} fields={this.fields()} />
          <div class="sticky-save-btn">
            <AsyncButton
              text="Save"
              class="btn"
              pendingClass="small spinner"
              onSubmit={onSubmit}
            />
          </div>
        </Form>
      </div>
    );
  }
}
