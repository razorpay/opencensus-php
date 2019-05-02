import React from 'react';
import Form from 'ui/Form';
import { SelectField, TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { adminPatch } from 'common/fetch';
import { closeModal, notifySuccess } from 'common/modal';

function sanitizeIINs(commaSeparatedIINs) {
  return commaSeparatedIINs.split(",")
    .map(p => p.trim())
    .filter(p => p.length === 6);
}

BulkEditIIN.title = 'Bulk Edit IIN';
export default function BulkEditIIN() {
  return (
    <Form class="full-span">
      <TextAreaField
        label="IINs"
        type="text"
        name="iins"
        required
        placeholder="Enter comma separated IINs"
      />
      <SelectField label="Action" name="action" required>
        <option value="">Select Action</option>
        <option value="enable">Enable</option>
        <option value="disable">Disable</option>
      </SelectField>
      <SelectField label="Flow" name="flow" required>
        <option value="">Select Flow</option>
        <option value="3ds">3ds</option>
        <option value="pin">pin</option>
        <option value="otp">otp</option>
        <option value="iframe">iframe</option>
        <option value="magic">magic</option>
        <option value="headless_otp">headless_otp</option>
        <option value="ivr">ivr</option>
      </SelectField>
      <AsyncButton
        text="Submit"
        class="btn"
        pendingClass="small spinner"
        type="submit"
        onSubmit={formData => {

          const data = {
            iins: sanitizeIINs(formData.iins),
            payload: {
              flows: {
                [formData.flow]: Number(formData.action === 'enable')
              }
            }
          };

          return adminPatch({
            url: `live/iins/bulk`,
            data
          }).then(data => {
            if (data) {
              const count = Object.keys(data).length;
              notifySuccess(`${count} IINs updated successfully.`);
              closeModal();
            }
          });
        }
        }
      />
    </Form>
  );
}
