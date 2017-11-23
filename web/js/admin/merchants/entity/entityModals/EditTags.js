import React from 'react';
import { toJS } from 'mobx';
import BaseModal from 'ui/BaseModal';

import Form from 'ui/Form';
import { TextAreaField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import { adminPost } from 'util/fetch';

export default ({ props, merchantId }) => {
  function onSubmit(body) {
    const tags = body.tags ? body.tags.split(',').map(tag => tag.trim()) : [];

    return adminPost({
      route_name: 'merchant_tag_add',
      url_params: {
        id: merchantId,
      },
      body: { tags },
    })
      .then(data => {
        if (data) {
          notifySuccess('Merchant tagged successfully.');
          props.updateDetails({ ...props.merchant.details, tags: data });
          closeModal();
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  }

  return (
    <BaseModal header="Edit Tags">
      <Form class="full-span full-elements" style={{ width: '450px' }}>
        <TextAreaField
          type="textarea"
          label="Tags"
          name="tags"
          defaultValue={toJS(props.merchant.details.tags).join(', ')}
        />

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
