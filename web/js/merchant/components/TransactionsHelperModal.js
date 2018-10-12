import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Group, { GroupItem } from 'rzp/ui/Group';
import { ModalMask, Modal, ModalContent } from 'component/Modal';
import Button from 'component/Button';

export default class InstantActivationSuccess extends Component {
  constructor(props) {
    super(props);
  }

  onCloseModal() {}

  render() {
    const { onClose } = this.props;

    return (
      <ModalMask>
        <Modal className="transactions-helper" onClose={onClose}>
          <modal-header>
            <h1>Start accepting payments</h1>
            <p>
              You can accept payments from your customers using the following
              methods
            </p>
          </modal-header>
          <modal-body>
            <Group>
              <GroupItem>
                <p>
                  <b>Accept payments on your website</b>
                </p>
                <p>
                  Integrate Razorpay onto your website. Want to know how to
                  integrate?
                </p>
                <Button.Secondary>Read Integration Docs</Button.Secondary>
              </GroupItem>
              <GroupItem>
                <p>
                  <b>Accept payments using products</b>
                </p>
                <p>
                  You can receive Payment through Payment Links, Invoices &
                  Smart Collect
                </p>
                <Button.Secondary>Read Integration Docs</Button.Secondary>
              </GroupItem>
            </Group>
          </modal-body>
        </Modal>
      </ModalMask>
    );
  }
}
