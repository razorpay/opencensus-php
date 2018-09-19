import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField, SelectMode } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPut } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

BeamNodalAction.title = 'Nodal Account Action via Beam';
BeamNodalAction.permission = 'settlement_bulk_update';

export default function BeamNodalAction() {
  return (
    <Form class="full-span">
      <Field label="File ID" type="text" name="file_id" required/>

        <SelectField label="File Type" name="file_type" required={true}>
            {Object.keys(filetypes).map(opt => (
                <option value={opt} key={opt}>
                    {filetypes[opt]}
                </option>
            ))}
        </SelectField>

        <SelectField label="Channel" name="channel" required={true}>
            {Object.keys(options).map(opt => (
                <option value={opt} key={opt}>
                    {options[opt]}
                </option>
            ))}
        </SelectField>

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

const options = {
    icici: 'ICICI',
    axis: 'Axis',
    hdfc: 'HDFC',
};

const filetypes = {
    settlement: 'Settlement',
    beneficiary: 'Beneficiary',
};