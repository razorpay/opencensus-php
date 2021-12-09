import React from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import { classList } from 'common/utils/rzp-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { saveQRCode } from 'merchant/reducers/qrCodes/list';
import { luminateRow } from 'merchant/reducers/app';
import QRCodePreviewModal from '../components/QRPreviewModal';
import Form from './Form';
import track from './track';

@withRouter
@connect(null, {
  luminateRow,
  showNotification,
  saveQRCode,
  ...ModalActions,
})
@RTracking(() => window.rzpQ.component('CreateQRCode'))
export default class CreateQRCode extends React.Component {
  isModalView = !!this.props.onClose;

  componentWillMount() {
    track.init({
      track: this.props.tracking.trackEvent,
    });
  }

  onSubmit = (reqPayload) => {
    track.submit();

    return this.props
      .saveQRCode(reqPayload)
      .then((resp) => {
        this.showPreview(resp.data);

        this.props.showNotification({
          type: 'success',
          message: 'QR code successfully created.',
        });

        track.submitSuccess(reqPayload);
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];

        this.props.showNotification({
          type: 'error',
          message: error,
        });

        track.submitFail(error);
      });
  };

  showPreview = ({ id, image_url }) => {
    this.props.openModal({
      size: 'medium',
      className: 'QRCode--Preview',
      disableClose: true,
      component: (
        <QRCodePreviewModal
          id={id}
          history={this.props.history}
          previewImg={image_url}
          closeModal={() => {
            this.props.closeModal();

            track.backToDashboard();
          }}
          onDownloadQRCode={track.downloadImage}
        />
      ),
    });
  };

  onClose = () => {
    this.props.onClose();

    track.cancel();
  };

  render() {
    const content = (
      <Form
        isModalView={this.isModalView}
        onClose={this.onClose}
        onSubmit={this.onSubmit}
        history={this.props.history}
      />
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
