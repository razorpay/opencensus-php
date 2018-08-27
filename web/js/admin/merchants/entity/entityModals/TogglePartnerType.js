import React from 'react';

import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { SelectField } from 'ui/Field';
import { ModalContent } from 'component/Modal';

import { adminPost } from 'common/fetch';

import { snakeToTitleCase } from 'common/util';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

const RequestExistenceMessage = ({ requestLabel }) => (
  <div>
    Request for <strong>{requestLabel} as Partner</strong> is already pending
    for this merchant
    <div>
      <button class="btn" onClick={closeModal}>
        Ok
      </button>
    </div>
  </div>
);

export default ({ props, merchantId }) => {
  const partnerRequests = props.merchant.partnerRequests;
  const isPartner = !!props.merchant.details.partner_type;
  const requestName = isPartner ? 'deactivation' : 'activation';
  const requestLabel = isPartner ? 'Remove' : 'Mark';

  const handleSubmit = ({ type: partner_type }) => {
    return adminPost({
      url: `live_${merchantId}/merchant/requests`,
      data: {
        name: requestName,
        type: 'partner',
        submissions: isPartner ? undefined : { partner_type },
      },
    })
      .then(response => {
        if (response) {
          props.updatePartnerRequests({
            [requestName + 'Pending']: true,
          });
          notifySuccess(
            `Request for ${requestLabel} as Partner submitted successfully`
          );
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  const renderPartnerDeactivation = () =>
    partnerRequests.deactivationPending ? (
      <RequestExistenceMessage requestLabel={requestLabel} />
    ) : (
      <div>
        Are you sure you want to <strong>Remove merchant as partner</strong> ?
        <div class="action-buttons">
          <AsyncButton
            text="Yes"
            class="btn"
            pendingClass="small spinner"
            onClick={handleSubmit}
          />
          <button onClick={closeModal} class="btn-reject">
            Cancel
          </button>
        </div>
      </div>
    );

  const renderPartnerActivation = () =>
    partnerRequests.activationPending ? (
      <RequestExistenceMessage requestLabel={requestLabel} />
    ) : (
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
    );

  return (
    <ModalContent header={requestLabel + ' as Partner'}>
      {isPartner ? renderPartnerDeactivation() : renderPartnerActivation()}
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
