import React from 'react';
import { toJS } from 'mobx';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import Field from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

export default ({ props }) => {
  function onSubmit(body) {
    const tags = toJS(props.merchant.details.tags);
    tags.push('ref-' + body.referral);

    return adminPost({
      route_name: 'merchant_tag_add',
      url_params: {
        id: props.merchant.details.id,
      },
      body: {
        tags: tags,
      },
    })
      .then(response => {
        if (response) {
          notifySuccess('Merchant tagged successfully.');
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Mark as Referred">
      <Form class="full-span full-elements" style={{ width: '350px' }}>
        <Field label="Merchant Id" name="referral" />

        <div class="m-t m-b info-block text-danger">
          <strong>
            Warning: You need to be a superadmin in order to edit merchant email
            address.
          </strong>
        </div>

        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          onSubmit={onSubmit}
        />
      </Form>
    </BaseModal>
  );
};
