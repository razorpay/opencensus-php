import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import VirtualAccountDetails from 'merchant/components/VirtualAccounts/Details';
import * as VirtualAccountActions from 'merchant/modules/virtualaccounts';
import { showNotification } from 'rzp/modules/notifications';

@withRouter
@connect(state => state.virtualaccount, {
  showNotification,
  ...VirtualAccountActions,
})
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
      message: 'The account will be closed and your customers will no longer be able to transfer money to this virtual account.',
      affirmativeLabel: 'Close',
      affirmativePendingLabel: 'Closing...',
      abortLabel: "No, don't",
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

  deleteAccount = virtualaccount => {
    this.context.confirm({
      message: () => (
        <span>
          The account will be closed and all the data for this account will be deleted.
          {' '}
          <b>You can’t undo this action.</b>
        </span>
      ),
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        this.props
          .deleteVirtualAccount(virtualaccount)
          .then(response => {
            this.props.history.push('/virtualaccounts');
            this.props.showNotification({
              type: 'success',
              message: 'Account deleted successfully',
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

  render() {
    let { loading, error, entity, va_payments } = this.props;
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
        isLoading={loading}
        statusMsg={statusMsg}
        onClose={this.closeAccount}
        onDelete={this.deleteAccount}
      />
    );
  }
}
