import { ModalMask, Modal, ModalContent } from 'component/Modal';
import React, { Component } from 'react';
import PartnerOnbr from './partnerOnbr';
import { connect } from 'react-redux';
import { updateSession } from 'merchant/modules/session';
import User from 'merchant/models/User';
@connect(
  state => ({
    user: state.session.user,
  }),
  { updateSession }
)
export class onboardPartner extends Component {
  state = { showModal: true };

  closeModal = () => {
    this.setState({
      showModal: false,
    });
    const userval = new User({
      ...this.props.user,
      partner_intent: false,
      partner_type: null,
      merchant_partner_intent: false,
    });
    this.props.updateSession({ user: userval });
  };
  render() {
    if (!this.state.showModal) {
      return null;
    }
    const disMissableModal = Boolean(this.props.user.merchant_partner_intent);
    return (
      <ModalMask maskClosable={disMissableModal} isBlur={false}>
        <Modal
          showCloseBtn={disMissableModal}
          className="top-40"
          onCloseCB={this.closeModal}
          onClose={this.closeModal}
        >
          <ModalContent>
            <PartnerOnbr closeModal={this.closeModal} />
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}

export default onboardPartner;
