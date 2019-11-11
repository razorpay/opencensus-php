import { connect } from 'react-redux';

import { closeModal, openModal } from 'merchant_common/reducers/modals';

import Button from 'component/Button';

import TransfersPreviewModal from './TransfersPreviewModal';

@connect(null, {
  openModal,
  closeModal,
})
export default class ShowTransferPreviewModal extends React.PureComponent {
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
