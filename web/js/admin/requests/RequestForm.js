import React from 'react';
import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyDone } from 'common/modal';

export default function RequestForm({
  requestState,
  title,
  description,
  onSubmit,
}) {
  const isDisabled = requestState !== 'approved' && requestState !== 'open';

  return (
    <Form class="full-span full-elements" onSubmit={onSubmit}>
      <Field
        name="title"
        label="Title"
        required
        defaultValue={title}
        placeholder={isDisabled ? '-- Cannot edit --' : ''}
        disabled={isDisabled}
      />
      <Field
        name="description"
        label="Description"
        required
        defaultValue={description}
        placeholder={isDisabled ? '-- Cannot edit --' : ''}
        disabled={isDisabled}
      />
      <AsyncButton
        text="Save"
        class="pull-right btn"
        pendingClass="small spinner"
        onSubmit={onSubmit}
      />
    </Form>
  );
}
