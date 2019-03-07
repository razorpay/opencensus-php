import React from 'react';

import Field, { SelectField, TextAreaField } from 'ui/Field';
import Form from 'ui/Form';
import { adminPost } from 'common/fetch';
import {
  notifyError,
  notifySuccess,
  openModal,
  closeModal,
} from 'common/modal';
import { ModalContent } from 'component/Modal';

GenerateVaultToken.title = 'Tokenize input data';
GenerateVaultToken.permission = 'make_api_call';

export default function GenerateVaultToken() {
  function onSubmit(data) {
    if (!data.secret) {
      notifyError('Please enter a secret.');
      return;
    }

    let payload = {
      url: `live/vault_token_create`,
      data: {
        namespace: data.namespace,
        secret: JSON.stringify(data.secret),
      },
    };

    adminPost(payload).then(response => {
      if (response) {
        notifySuccess('Token created successfully.');
        closeModal();
        openModal(
          <ModalContent header="API Response" noPadding>
            <div class="code" style={{ width: '650px' }}>
              {JSON.stringify(response, null, 4)}
            </div>
          </ModalContent>
        );
      }
    });
  }

  return (
    <Form class="full-span full-elements" onSubmit={onSubmit}>
      <SelectField label="Namespace" name="namespace" required={true}>
        {Object.keys(options).map(opt => (
          <option value={opt} key={opt}>
            {options[opt]}
          </option>
        ))}
      </SelectField>
      <TextAreaField
        label="Secret"
        name="secret"
        placeholder="Enter secret here"
      />
      <div class="form-actions text-right">
        <button class="btn" type="submit">
          Generate Token
        </button>
      </div>
    </Form>
  );
}

const options = {
  nodal_certs: 'nodal_certs',
};
