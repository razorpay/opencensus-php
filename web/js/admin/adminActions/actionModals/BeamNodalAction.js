import React from 'react';
import Form from 'ui/Form';
import Field, { SelectMode } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPut } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

BeamNodalAction.title = 'Nodal Account Action via Beam';
BeamNodalAction.title = 'settlement_bulk_update';
export default function BeamNodalAction() {
  return (
    <Form class="full-span">
      <Field label="File ID" type="text" name="file_id" required/>
      <Field label="File Type" type="text" name="file_type" required />
      <Field label="Channel" type="text" name="channel" required/>
      <SelectMode defaultValue="live" />
      <AsyncButton
        text="Send File"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={body => {
          return adminPut({
            url: `${body.mode}/nodal_file_upload/retry`,
            data: {
                file_id: body.file_id || undefined,
                file_type: body.file_type || undefined,
                channel: body.channel || undefined,
            },
          }).then(response => {
            if (response) {
              notifySuccess('File-send request sent!');
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}
