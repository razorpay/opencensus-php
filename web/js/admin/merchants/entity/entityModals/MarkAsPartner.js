import React from 'react';

import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { SelectField } from 'ui/Field';
import { ModalContent } from 'component/Modal';

import { adminPost } from 'common/fetch';

import { snakeToTitleCase } from 'common/util';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

export default ({ props, merchantId }) => {
  const handleSubmit = ({ type: partner_type }) => {
    return adminPost({
      url: 'live_' + merchantId + '/merchant/requests',
      data: {
        name: 'activation',
        type: 'partner',
        submissions: { partner_type },
      },
    })
      .then(response => {
        if (response) {
          props.updateDetails(response.merchant);
          notifySuccess('Merchant marked as partner');
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  return (
    <ModalContent header="Mark as Partner">
      <Form class="full-span full-elements" style={{ width: '350px' }}>
        <SelectField label="Partner Type" name="type" defaultValue="">
          <option>Select Type...</option>
          {partnerTypes.map(type => (
            <option key={type} value={type}>
              {snakeToTitleCase(type)}
            </option>
          ))}
        </SelectField>
        <AsyncButton
          text="Submit"
          class="btn"
          pendingClass="small spinner"
          onSubmit={handleSubmit}
        />
      </Form>
    </ModalContent>
  );
};

var partnerTypes = [
  'bank',
  'reseller',
  'aggregator',
  'fully_managed',
  'pure_platform',
];
