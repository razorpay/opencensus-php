import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import VirtualAccountDetails from 'merchant/components/VirtualAccounts/Details';
import * as VirtualAccountActions from 'merchant/modules/virtualaccounts/list';
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
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  closeAccount = virtualaccount => {
    this.context.confirm({
      message: 'Are you sure to close the account?',
      affirmativeLabel: 'Close',
      affirmativePendingLabel: 'Closing...',
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
      message: 'Are you sure to delete the account?',
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
    let { loading, error, entity } = this.props;
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
        isLoading={loading}
        statusMsg={statusMsg}
        onClose={this.closeAccount}
        onDelete={this.deleteAccount}
      />
    );
  }
}
