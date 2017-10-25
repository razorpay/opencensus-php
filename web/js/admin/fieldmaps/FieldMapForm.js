import React from 'react';

import Form from 'ui/Form';
import Field, { TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

export default function FieldMapForm({
  id,
  entity_name,
  fields = [],
  org_id,
  onSubmit,
}) {
  return (
    <div>
      <header>{id ? `Edit - ${id}` : 'Add a new Field Map'}</header>
      <Form>
        {id ? <input type="hidden" name="id" defaultValue={id} /> : ''}

        <Field
          label="Entity Name"
          name="entity_name"
          defaultValue={entity_name}
          required
        />
        <br />
        <TextAreaField
          label="Fields"
          name="fields"
          defaultValue={fields.join(',')}
          required
        />
        <br />
        <AsyncButton
          text="SAVE"
          class="btn"
          pendingClass="small spinner"
          onSubmit={onSubmit}
        />
      </Form>
    </div>
  );
}
