import React, { Component } from 'react';
import { observer } from 'mobx-react';

import { closeModal, notifyError, notifySuccess } from 'common/modal';
import BaseModal from 'ui/BaseModal';
import Field from 'ui/Field';
import Form from 'ui/Form';
import AsyncButton from 'ui/AsyncButton';
import { adminFetch, adminPost } from 'common/fetch';

@observer
export default class EditMerchant extends Component {
  handleConfirm = body => {
    return adminPost(body, '/admin/merchant/' + this.props.merchantId + '/edit')
      .then(data => {
        if (data) {
          closeModal();
          notifySuccess('Merchant Fraud Score updated successfully');
          this.props.props.updateDetails(data);
        }
      })
      .catch(err => {
        notifyError(err);
      });
  };

  render() {
    const { details } = this.props.props.merchant;
    return (
      <BaseModal header="Edit Fraud Score">
        <Form class="full-span full-elements" style={{ width: '450px' }}>
          <Field
            label="Risk Threshold"
            name="risk_threshold"
            defaultValue={details.risk_threshold}
            type="number"
            min="5"
            max="20"
            placeholder="Valid range: 5 - 20"
          />
          <AsyncButton
            text="Save"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleConfirm}
          />
        </Form>
      </BaseModal>
    );
  }
}
