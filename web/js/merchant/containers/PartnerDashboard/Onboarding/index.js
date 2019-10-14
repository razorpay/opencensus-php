import { ModalMask, Modal, ModalContent } from 'component/Modal';
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import PartnerOnbr from './partnerOnbr';
export class onboardPartner extends Component {
  static propTypes = {
    prop: PropTypes,
  };

  render() {
    return (
      <ModalMask maskClosable={false} isBlur={false}>
        <Modal showCloseBtn={false} className="top-40">
          <ModalContent>
            <PartnerOnbr />
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}

export default onboardPartner;
