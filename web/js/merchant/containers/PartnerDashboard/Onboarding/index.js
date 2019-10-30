import { ModalMask, Modal, ModalContent } from 'component/Modal';
import React, { Component } from 'react';
import PartnerOnbr from './partnerOnbr';
import { connect } from 'react-redux';
import { updateSession } from 'merchant/modules/session';
import User from 'merchant/models/User';

import { openModal, closeModal } from 'rzp/modules/modals';

@connect(
  state => ({
    user: state.session.user,
  }),
  {
    updateSession,
    openModal,
    closeModal,
  }
)
export class onboardPartner extends Component {
  showModal = component => {
    const disableClose = !Boolean(this.props.user.merchant_partner_intent);
    this.props.openModal({
      size: 'xlarge',
      disableClose: disableClose,
      component: (
        <PartnerOnbr
          closeModal={this.props.closeModal}
          disableClose={disableClose}
        />
      ),
    });
  };
  componentDidMount() {
    this.showModal();
  }
  render() {
    return null;
  }
}

export default onboardPartner;
