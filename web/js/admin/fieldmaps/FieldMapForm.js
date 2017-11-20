import React from 'react';

import Form from 'ui/Form';
import Field, { TextAreaField } from 'ui/Field';
import BaseModal from 'ui/BaseModal';
import AsyncButton from 'ui/AsyncButton';

export default function FieldMapForm({
  id,
  entity_name,
  fields = [],
  org_id,
  onSubmit,
}) {
  return (
    <BaseModal header={id ? `Edit - ${id}` : 'Add a new Field Map'}>
      <Form class="full-span full-elements" style={{ width: '400px' }}>
        {id ? <input type="hidden" name="id" defaultValue={id} /> : ''}

        <Field
          label="Entity Name"
          name="entity_name"
          defaultValue={entity_name}
          required
        />
        <TextAreaField
          label="Fields (Seperated by comma's)"
          name="fields"
          defaultValue={fields.join(',')}
          required
        />
        <AsyncButton
          text="SAVE"
          class="btn"
          pendingClass="small spinner"
          onSubmit={onSubmit}
        />
      </Form>
    </BaseModal>
  );
}
