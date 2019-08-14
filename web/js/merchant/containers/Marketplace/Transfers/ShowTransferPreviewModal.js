import { connect } from 'react-redux';

import { openModal, closeModal } from 'common/modal';

import TransfersPreviewModal from './TransfersPreviewModal';

connect(null, {
  openModal,
  closeModal,
});
export default class ShowTransferPreviewModal extends React.PureComponent {
  openTransfersPreviewModal = () => {
    this.props.openModal({
      size: 'small',
      component: <TransfersPreviewModal closeModal={this.props.closeModal} />,
    });
  };

  render() {
    return (
      <div onClick={this.openTransfersPreviewModal}>{this.props.children}</div>
    );
  }
}
