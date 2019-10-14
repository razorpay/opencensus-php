import { ModalMask, Modal, ModalContent } from 'component/Modal';
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import PartnerOnbr from './partnerOnbr';
export class onboardPartner extends Component {
  state = { showModal: true };
  static propTypes = {
    prop: PropTypes,
  };

  closeModal = () => {
    this.setState({
      showModal: false,
    });
  };
  render() {
    if (!this.state.showModal) {
      return null;
    }
    return (
      <ModalMask maskClosable={false} isBlur={false}>
        <Modal showCloseBtn={false} className="top-40">
          <ModalContent>
            <PartnerOnbr closeModal={this.closeModal} />
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}

export default onboardPartner;
