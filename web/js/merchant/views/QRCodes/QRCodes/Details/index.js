import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';

import QRCodeDetails from './Details';
import { showNotification } from 'merchant_common/reducers/notifications';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { fetchPayments, fetchDetails } from './model';
import { fetchCustomersForAutocomplete } from 'merchant/reducers/customers';
import { closeQR } from 'merchant/reducers/qrCodes/list';
import CreateTestPayment from './CreateTestPayment';
import QRCodePreviewModal from '../components/QRPreviewModal';
import track from './track';

const QR_CODE_DETAILS_HOTJAR = {
  trigger: 'QR_Details',
  tags: ['QR_Details'],
};
@withRouter
@connect(
  (state) => {
    return {
      isTestMode: state.session.mode === 'test',
      customers: state.customers,
    };
  },
  {
    openModal,
    closeModal,
    showNotification,
    fetchCustomersForAutocomplete,
    closeQR,
  },
)
@RTracking(() => window.rzpQ.component('QRCodeDetailsContainer'))
export default class QRCodeDetailsContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    isLoading: false,
    entity: {},
    payments: [],
    isPaymentsLoading: false,
  };

  componentDidMount() {
    const { id } = this.props;
    this.fetchQRCodeDetails(id);
    this.fetchPayments(id);
    this.props.fetchCustomersForAutocomplete();
    triggerHotjarRecording(QR_CODE_DETAILS_HOTJAR.trigger, QR_CODE_DETAILS_HOTJAR.tags);
    track.init({
      track: this.props.tracking.trackEvent,
    });

    track.open();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchQRCodeDetails(nextProps.id);
      this.fetchPayments(nextProps.id);
    }
  }

  fetchQRCodeDetails = (id = this.props.id) => {
    this.setState({
      isLoading: true,
    });

    return fetchDetails(id)
      .then((resp) => {
        this.setState({
          entity: resp,
          isLoading: false,
        });
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];
        this.setState({
          error,
          isLoading: false,
        });
      });
  };

  fetchPayments = (id) => {
    this.setState({
      isPaymentsLoading: true,
    });

    fetchPayments(id)
      .then((resp) => {
        this.setState({
          payments: resp.data.items,
          isPaymentsLoading: false,
        });
      })
      .catch(() => {
        this.setState({
          isPaymentsLoading: false,
        });
      });
  };

  closeAccount = () => {
    track.close();

    this.context.confirm({
      header: 'Close QR Code?',
      message:
        'The account will be closed and your customers will no longer be able to transfer money to this QR code.',
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () => {
        return this.props
          .closeQR(this.state.entity.id)
          .then((response) => {
            this.setState({
              entity: response.data,
            });

            this.props.showNotification({
              type: 'success',
              message: 'QR code closed successfully',
            });

            track.closeSuccess(true);
          })
          .catch(({ errors }) => {
            const error = (errors || [])[0];
            this.props.showNotification({
              type: 'error',
              message: error,
            });

            track.closeSuccess(false, error);
          });
      },
      abort: () => {},
    });
  };

  openTestPaymentModal = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <CreateTestPayment
          qrCode={this.props.entity}
          onMount={this.onTestPaymentModalMount}
          fetchQRCodeDetails={this.fetchQRCodeDetails}
          fetchQRPayments={() => this.fetchQRPayments(this.state.entity.id)}
        />
      ),
    });
  };

  showPreview = () => {
    this.props.openModal({
      size: 'medium',
      className: 'QRCode--Preview',
      component: (
        <QRCodePreviewModal
          id={this.state.entity}
          previewImg={this.state.entity.image_url}
          closeModal={this.props.closeModal}
        />
      ),
    });
    track.preview();
  };

  downloadQRCode = () => {
    window.open(this.state.entity.image_url);
    track.downloadQR();
  };

  componentWillUnmount() {
    track.unmountDetailsView();
  }

  render() {
    const { isLoading, error, entity, payments, isPaymentsLoading } = this.state;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
      };
    }

    return (
      <QRCodeDetails
        isPaymentsLoading={isPaymentsLoading}
        key={this.props.id}
        qrCode={entity}
        payments={payments}
        isLoading={isLoading}
        statusMsg={statusMsg}
        onClose={this.closeAccount}
        customers={this.props.customers}
        isTestMode={this.props.isTestMode}
        onMakeTestPaymentClick={this.openTestPaymentModal}
        showPreview={this.showPreview}
        downloadQRCode={this.downloadQRCode}
        viewAllPayments={track.viewAllPayments}
      />
    );
  }
}
