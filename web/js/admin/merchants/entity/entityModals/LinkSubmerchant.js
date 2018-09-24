import { Component } from 'react';

import { adminPost, adminDelete } from 'common/fetch';
import { notifySuccess } from 'common/modal';

import { ModalContent } from 'component/Modal';
import Form from 'ui/Form';
import Field from 'ui/Field';
import Table from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';

const getSubmerchantFields = unlinkSubmerchant => [
  ['Merchant Id', item => item.id],
  [
    'Merchant Name',
    item =>
      item.name || <em class="info-block">To view name please refresh</em>,
  ],
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
        this.setState(
          {
            submerchants: [...this.state.submerchants].concat({
              id: 'acc_' + response.merchant_id,
            }),
          },
          this.updateModelWithCurrentState
        );
      }
    });
  };

  unlinkSubmerchant = submerchantId => {
    const { merchantId } = this.props;
    adminDelete({
      url: `live_${merchantId}/merchants/${submerchantId.replace(
        'acc_',
        ''
      )}/access_maps`,
      headers: { ['X-Razorpay-Account']: merchantId },
    }).then(response => {
      if (response) {
        notifySuccess('Submerchant unlinked successfully');
        this.setState(
          {
            submerchants: this.state.submerchants.filter(
              ({ id }) => id !== submerchantId
            ),
          },
          this.updateModelWithCurrentState
        );
      }
    });
  };

  updateModelWithCurrentState = () => {
    const { props: model } = this.props;
    model.updateSubmerchants(this.state.submerchants);
  };

  render() {
    return (
      <ModalContent header="Link Sub-Merchant">
        <Form class="full-span full-elements">
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
