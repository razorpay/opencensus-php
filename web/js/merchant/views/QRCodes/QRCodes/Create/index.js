import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import { classList } from 'common/utils/rzp-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { saveQRCode } from 'merchant/reducers/qrCodes/list';
import { luminateRow } from 'merchant/reducers/app';
import Form from './Form';

@withRouter
@connect(null, {
  luminateRow,
  showNotification,
  saveQRCode,
  ...ModalActions,
})
export default class CreateQRCode extends React.Component {
  isModalView = !!this.props.onClose;

  onSubmit = (reqPayload) => {
    return this.props
      .saveQRCode(reqPayload)
      .then((resp) => {
        const entityId = resp.data.id;

        if (this.isModalView) {
          this.props.closeModal();

          this.props.luminateRow(entityId);
        }

        const redirectUrl = '/qr_codes/' + entityId;
        this.props.history.push(redirectUrl);

        this.props.showNotification({
          type: 'success',
          message: 'QR code successfully created',
        });
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];

        this.props.showNotification({
          type: 'error',
          message: error,
        });
      });
  };

  render() {
    const content = (
      <Form isModalView={this.isModalView} onClose={this.props.onClose} onSubmit={this.onSubmit} />
    );

    return this.isModalView ? (
      <Modal class={classList('QRCode--Create', content && 'animate-down')} showCloseBtn={false}>
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{content}</div>
    );
  }
}
