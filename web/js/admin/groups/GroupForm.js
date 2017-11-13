import React, { Component } from 'react';
import Field, { SelectField, SelectMethod } from 'ui/Field';
import Table from 'ui/Table';
import Form from 'ui/Form';

export default class GroupForm extends Component {
  deleteParentField = () => {
    let { onDeleteParent } = this.props;
    return [
      'Action',
      item => (
        <div class="link danger" onClick={e => onDeleteParent(item)}>
          Remove
        </div>
      ),
    ];
  };

  render() {
    let {
      parents,
      potentialParents,
      onSubmit,
      onSelectParent,
      group,
    } = this.props;
    return (
      <div>
        <header>Edit Group</header>
        <Form onSubmit={onSubmit}>
          <Field
            name="name"
            label="Name"
            required
            defaultValue={group && group.name}
          />
          <Field
            name="description"
            label="Description"
            required
            defaultValue={group && group.description}
          />
          <br />
          <SelectField label="Parents" onChange={onSelectParent} value="">
            <option value="" />
            {potentialParents.map(p => (
              <option value={p.id} key={p.id}>
                {p.name}
              </option>
            ))}
          </SelectField>
          <button>Save</button>
          {parents.length
            ? [
                <label key="label">Parents</label>,
                <Table
                  fields={fields.concat([this.deleteParentField()])}
                  items={parents}
                  border={true}
                  key="table"
                />,
              ]
            : null}
          <br />
          {group && group.sub_groups.length
            ? [
                <label key="label">Subgroups</label>,
                <Table
                  fields={fields}
                  items={group.sub_groups}
                  border={true}
                  key="table"
                />,
              ]
            : null}
        </Form>
      </div>
    );
  }
}

const fields = [
  ['Group Id', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
];
