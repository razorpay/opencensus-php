import { Component } from 'react';

import { adminPost } from 'common/fetch';
import { notifySuccess, closeModal } from 'common/modal';

import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import Field from 'ui/Field';
import Table from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';

const getSubmerchantFields = unlinkSubmerchant => [
  ['Merchant Id', item => item.id],
  ['Merchant Name', item => item.name],
  [
    'Actions',
    item => (
      <AsyncButton
        class="link danger"
        onClick={() => unlinkSubmerchant(item.id)}
      >
        Unlink
      </AsyncButton>
    ),
  ],
];

export default class LinkSbmerchant extends Component {
  state = {
    submerchants: this.props.props.merchant.submerchants,
  };

  handleSubmit = ({ submerchantId }) => {
    const { merchantId } = this.props;
    return adminPost({
      url: `live_${merchantId}/merchants/${submerchantId}/access_maps`,
      headers: { ['X-Razorpay-Account']: merchantId },
    }).then(response => {
      if (response) {
        notifySuccess('Sub merchant added successfully');
        closeModal();
      }
    });
  };

  unlinkSubmerchant = submerchantId => {
    this.props.props.unlinkSubmerchant(submerchantId).then(response => {
      if (response) {
        this.setState({
          submerchants: this.state.submerchants.filter(
            ({ id }) => id !== submerchantId
          ),
        });
      }
    });
  };

  render() {
    return (
      <ModalContent header="Link Sub-Merchant">
        <Form class="full-span full-elements" style={{ width: '350px' }}>
          <Field label="Merchant Id" name="submerchantId" />

          <AsyncButton
            text="Add"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleSubmit}
          />
        </Form>

        <div class="m-t">
          <strong>Linked Submerchants</strong>
        </div>
        <Table
          items={this.state.submerchants}
          fields={getSubmerchantFields(this.unlinkSubmerchant)}
        />
      </ModalContent>
    );
  }
}
