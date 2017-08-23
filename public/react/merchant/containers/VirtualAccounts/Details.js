import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import VirtualAccountDetails from 'merchant/components/VirtualAccounts/Details';
import * as VirtualAccountActions from 'merchant/modules/virtualaccounts';
import { showNotification } from 'rzp/modules/notifications';
import { openModal } from 'rzp/modules/modals';
import CreateTestPayment from './CreateTestPayment';

@withRouter
@connect(
  state => {
    return {
      ...state.virtualaccount,
      mode: state.session.mode,
    };
  },
  {
    openModal,
    showNotification,
    ...VirtualAccountActions,
  }
)
export default class VirtualAccountDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentWillMount() {
    let { id } = this.props;
    this.props.fetchItem(id);
    this.props.fetchVAPayments(id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  closeAccount = virtualaccount => {
    this.context.confirm({
      header: 'Close account?',
      message: 'The account will be closed and your customers will no longer be able to transfer money to this virtual account.',
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () =>
        this.props
          .saveVirtualAccount({ ...virtualaccount, status: 'closed' })
          .then(response => {
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
          }),
    });
  };

  openTestPaymentModal = () => {
    this.props.openModal({
      size: 'small',
      component: <CreateTestPayment virtualAccount={this.props.entity} />,
    });
  };

  render() {
    let { loading, error, entity, va_payments, mode } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <VirtualAccountDetails
        virtualaccount={entity}
        va_payments={va_payments}
        mode={mode}
        isLoading={loading}
        statusMsg={statusMsg}
        onClose={this.closeAccount}
        onMakeTestPaymentClick={this.openTestPaymentModal}
      />
    );
  }
}
