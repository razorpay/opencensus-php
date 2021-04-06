import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import QRCodeDetails from './Details';
import * as VirtualAccountActions from 'merchant/reducers/virtualaccounts';
import { showNotification } from 'merchant_common/reducers/notifications';
import { openModal } from 'merchant_common/reducers/modals';
import { fetchPayments, fetchQRCodeDetails } from './model';
import { fetchCustomersForAutocomplete } from 'merchant/reducers/customers';

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
    showNotification,
    fetchCustomersForAutocomplete,
  },
)
export default class VirtualAccountDetailsContainer extends React.Component {
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

  closeAccount = (virtualaccount) => {
    this.context.confirm({
      header: 'Close account?',
      message:
        'The account will be closed and your customers will no longer be able to transfer money to this virtual account.',
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () => {
        this.props
          .closeVirtualAccount({ ...virtualaccount, status: 'closed' })
          .then((response) => {
            this.props.showNotification({
              type: 'success',
              message: 'Account closed successfully',
            });
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
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
          virtualAccount={this.props.entity}
          onMount={this.onTestPaymentModalMount}
        />
      ),
    });
  };

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
      />
    );
  }
}
