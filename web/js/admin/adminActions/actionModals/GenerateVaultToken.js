import React from 'react';

import Field, { SelectField, TextAreaField } from 'ui/Field';
import Form from 'ui/Form';
import { adminPost } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

GenerateVaultToken.title = 'Tokenize input data';
GenerateVaultToken.permission = 'make_api_call';

export default function GenerateVaultToken() {
  return (
    <Form class="full-span full-elements">
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
      <AsyncButton
        text="Create Token"
        class="btn"
        pendingClass="small spinner"
        onSubmit={data => {
          if (!data.secret) {
            return notifyError('Please enter a secret.');
          }
          return adminPost({
            url: 'live/vault_token_create',
            data,
          }).then(response => {
            if (response) {
              notifySuccess('Token created successfully.');
              openModal(
                <ModalContent header="API Response" noPadding>
                  <div class="code" style={{ width: '650px' }}>
                    {JSON.stringify(response, null, 4)}
                  </div>
                </ModalContent>
              );
              closeModal();
            }
          });
        }}
      />
    </Form>
  );
}

const options = {
  nodal_certs: 'nodal_certs',
};
