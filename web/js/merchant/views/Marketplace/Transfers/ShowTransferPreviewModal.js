import React from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import TransfersPreviewModal from './TransfersPreviewModal';

class ShowTransferPreviewModal extends React.PureComponent {
  openTransfersPreviewModal = () => {
    this.props.openModal({
      size: 'large',
      component: <TransfersPreviewModal closeModal={this.props.closeModal} />,
    });
  };

  render() {
    return (
      <Button.Transparent onClick={this.openTransfersPreviewModal}>
        {this.props.children}
      </Button.Transparent>
    );
  }
}

export default connect(null, {
  openModal,
  closeModal,
})(ShowTransferPreviewModal);
