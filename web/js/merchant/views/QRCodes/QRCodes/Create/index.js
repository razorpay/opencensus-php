/* eslint-disable react/no-unsafe */
import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { withRouter } from 'common/deprecated/withRouter';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import { classList } from 'common/utils/rzp-utils';
import { luminateRow } from 'merchant/reducers/app';
import { fetchFeatureStatus } from 'merchant/reducers/config';
import { saveQRCode, saveUPIQRCode } from 'merchant/reducers/qrCodes/list';
import QRCodePreviewModal from 'merchant/views/QRCodes/QRCodes/components/QRPreviewModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import Form from './Form';
import track from './track';

@connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  {
    luminateRow,
    showNotification,
    saveQRCode,
    saveUPIQRCode,
    fetchFeatureStatus,
    ...ModalActions,
  },
)
@RTracking(() => window.rzpQ.component('CreateQRCode'))
class CreateQRCode extends React.Component {
  state = {
    isUpiqr: false,
  };
  isModalView = !!this.props.onClose;

  UNSAFE_componentWillMount() {
    track.init({
      track: this.props.tracking.trackEvent,
    });
    // check upiqr_v1_hdfc MID feature
    this.fetchFeatureFlagStatus('upiqr_v1_hdfc', 'isUpiqr');
  }

  fetchFeatureFlagStatus = (flag, state) => {
    const { fetchFeatureStatus, user, showNotification } = this.props;
    fetchFeatureStatus(user?.id, flag)
      .then((response) => {
        if (response?.success) {
          this.setState((prevState) => ({
            ...prevState,
            [state]: response?.data?.status,
          }));
        }
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: `${errors?.join(' ')}`,
        });
      });
  };

  onSubmit = (reqPayload) => {
    const { isUpiqr } = this.state;
    track.submit();
    if (isUpiqr) {
      return this.onSaveUPIQRCode(reqPayload);
    } else {
      return this.onSaveQRCode(reqPayload);
    }
  };

  onSaveUPIQRCode = (reqPayload) => {
    const { saveUPIQRCode, showNotification } = this.props;
    reqPayload.receivers = {
      types: ['qr_code'],
      qr_code: {
        method: {
          card: false,
          upi: true,
        },
      },
    };
    if (reqPayload.fixed_amount) {
      reqPayload.amount_expected = reqPayload.payment_amount;
    }
    delete reqPayload.type;
    delete reqPayload.fixed_amount;
    delete reqPayload.payment_amount;
    return saveUPIQRCode(reqPayload)
      .then((resp) => {
        this.showPreview(resp.data);
        showNotification({
          type: 'success',
          message: 'QR code successfully created.',
        });
        track.submitSuccess(reqPayload);
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: `${errors?.join(' ')}`,
        });
        track.submitFail(errors?.[0]);
      });
  };

  onSaveQRCode = (reqPayload) => {
    const { saveQRCode, showNotification } = this.props;
    return saveQRCode(reqPayload)
      .then((resp) => {
        this.showPreview(resp.data);

        showNotification({
          type: 'success',
          message: 'QR code successfully created.',
        });

        track.submitSuccess(reqPayload);
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: `${errors.join(' ')}`,
        });
        track.submitFail(errors?.[0]);
      });
  };

  showPreview = ({ id, image_url }) => {
    const { openModal, closeModal, history } = this.props;
    openModal({
      size: 'medium',
      className: 'QRCode--Preview',
      disableClose: true,
      component: (
        <QRCodePreviewModal
          id={id}
          history={history}
          previewImg={image_url}
          closeModal={() => {
            closeModal();

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
    const { history } = this.props;
    const content = (
      <Form
        isModalView={this.isModalView}
        onClose={this.onClose}
        onSubmit={this.onSubmit}
        history={history}
      />
    );

    return this.isModalView ? (
      <Modal
        className={classList('QRCode--Create', content && 'animate-down')}
        showCloseBtn={false}
      >
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div className="StandAloneContainer">{content}</div>
    );
  }
}

export default withRouter(CreateQRCode);
