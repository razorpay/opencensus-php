import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import QRCodeDetails from './Details';
import * as VirtualAccountActions from 'merchant/reducers/virtualaccounts';
import { showNotification } from 'merchant_common/reducers/notifications';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { fetchPayments, fetchQRCodeDetails } from './model';
import { fetchCustomersForAutocomplete } from 'merchant/reducers/customers';
import { closeQR } from 'merchant/reducers/qrCodes/list';
import CreateTestPayment from './CreateTestPayment';
import QRCodePreviewModal from '../components/QRPreviewModal';

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
    closeQR
  },
)
export default class QRCodeDetailsContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    isLoading: false,
    entity: {},
    payments: [],
  };

  componentWillMount() {
    let { id } = this.props;
    this.fetchQRCodeDetails(id);
    this.fetchPayments(id);
    this.props.fetchCustomersForAutocomplete();
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchQRCodeDetails(nextProps.id);
      this.fetchPayments(nextProps.id);
    }
  }

  fetchQRCodeDetails = (id) => {
    this.setState({
      isLoading: true,
    });

    fetchQRCodeDetails(id)
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
    fetchPayments(id).then((resp) => {
      this.setState({
        payments: resp.data,
      });
    });
  };

  closeAccount = () => {
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
              entity: response.data
            });

            this.props.showNotification({
              type: 'success',
              message: 'QR code closed successfully',
            });
          })
          .catch(({ errors }) => {
            const error = (errors || [])[0];
            this.props.showNotification({
              type: 'error',
              message: error,
            });
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
  }

  downloadQRCode = () => {
    window.open(this.state.entity.image_url);
  }

  render() {
    let { isLoading, error, entity, payments } = this.state;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
      };
    }

    return (
      <QRCodeDetails
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
      />
    );
  }
}
