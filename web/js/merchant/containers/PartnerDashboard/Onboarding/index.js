import { ModalMask, Modal, ModalContent } from 'component/Modal';
import React, { Component } from 'react';
import PartnerOnbr from './partnerOnbr';
import { connect } from 'react-redux';

@connect(state => ({
  user: state.session.user,
}))
export class onboardPartner extends Component {
  state = { showModal: true };

  closeModal = () => {
    this.setState({
      showModal: false,
    });
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
