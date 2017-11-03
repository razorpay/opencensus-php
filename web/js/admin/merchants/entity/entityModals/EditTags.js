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
    const tags = body.tags ? body.tags.split(',') : [];

    return adminPost({
      route_name: 'merchant_tag_add',
      url_params: {
        id: props.merchant.details.id,
      },
      body: { tags },
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
    <BaseModal header="Edit Tags">
      <Form>
        <Field
          label="Tags"
          name="tags"
          defaultValue={toJS(props.merchant.details.tags).join(',')}
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
