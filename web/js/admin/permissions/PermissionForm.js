import React from 'react';
import Field, { SelectField, CheckField } from 'ui/Field';
import SimpleTable from 'ui/SimpleTable';
import Form from 'ui/Form';

export default function PermForm({
  id,
  name,
  description,
  category,
  perms,
  roles,
  orgs,
}) {
  console.log(orgs);
  return (
    <div>
      <header>{id ? `Edit Permission - ${id}` : 'Add a new Permission'}</header>
      <Form>
        <Field label="Permission Name" name="name" defaultValue={name} />
        <Field label={'Category'} name="category" defaultValue={category} />
        <br />
        <Field
          label={'Description'}
          name="description"
          defaultValue={description}
        />
        <CheckField
          label="Assignable"
          name="Assignable"
          defaultChecked={false}
          name=""
        />
        <header>Organizations:</header>
        <SimpleTable items={orgs && orgs.items} fields={orgFields} />
        <header>Assigned Roles(In this Org)</header>
        <SimpleTable items={roles && roles.items} fields={roleFields} />
        <button>Save</button>
      </Form>
    </div>
  );
}

const orgFields = [
  ['', item => <CheckField />],
  ['Business Name', item => item.business_name],
  ['Display Name', item => item.display_name],
  ['Workflow Enable', item => <CheckField disabled />],
];

const roleFields = [
  ['Name', item => item.name],
  ['Description', item => item.description],
];
