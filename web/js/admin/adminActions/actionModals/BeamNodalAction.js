import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField, SelectMode } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';
import { titleCase } from 'common/util';

BeamNodalAction.title = 'Nodal Account Action via Beam';
BeamNodalAction.permission = 'settlement_bulk_update';

export default function BeamNodalAction() {
  return (
    <Form class="full-span">
      <Field label="File ID" type="text" name="file_id" required />

      <SelectField label="File Type" name="file_type" required>
        {fileTypes.map(opt => (
          <option value={opt} key={opt}>
            {titleCase(opt)}
          </option>
        ))}
      </SelectField>

      <SelectField label="Channel" name="channel" required>
        {Object.keys(options).map(opt => (
          <option value={opt} key={opt}>
            {options[opt]}
          </option>
        ))}
      </SelectField>

      <SelectMode defaultValue="live" required />
      <AsyncButton
        text="Send File"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={({ mode, ...data }) => {
          return adminPost({
            url: `${mode}/nodal_file_upload/retry`,
            data,
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
  axis2: 'Power Access',
};

const fileTypes = ['settlement', 'beneficiary'];
