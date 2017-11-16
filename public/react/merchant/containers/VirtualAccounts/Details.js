import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import VirtualAccountDetails from 'merchant/components/VirtualAccounts/Details';
import * as VirtualAccountActions from 'merchant/modules/virtualaccounts';
import { showNotification } from 'rzp/modules/notifications';
import { openModal } from 'rzp/modules/modals';
import CreateTestPayment from './CreateTestPayment';
import {
  stringifyQueryParamsWithPipe,
  getEventCategoryFromPath,
} from 'rzp/utils/rzp-utils';

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

  componentDidMount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);

    console.log(closeUrl, id);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Open Details - Virtual Account',
        eventLabel: `virtual_account_id=${id}`,
      });
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Close Details - Virtual Account',
        eventLabel: `virtual_account_id=${id}`,
      });
  }

  closeAccount = virtualaccount => {
    this.context.confirm({
      header: 'Close account?',
      message:
        'The account will be closed and your customers will no longer be able to transfer money to this virtual account.',
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () =>
        this.props
          .saveVirtualAccount({ ...virtualaccount, status: 'closed' })
          .then(response => {
            window.rzpAnalytics({
              eventCategory: 'Dashboard - Smart Collect',
              eventAction: 'Submit Form - Close Virtual Account',
              eventLabel: `virtual_account_id=${virtualaccount.id}`,
            });

            window.rzpAnalytics({
              eventCategory: 'Dashboard - Smart Collect',
              eventAction: 'Close Form - Close Virtual Account',
              eventLabel: `virtual_account_id=${virtualaccount.id}`,
            });

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
      onMount: () => {
        window.rzpAnalytics({
          eventCategory: 'Dashboard - Smart Collect',
          eventAction: 'Open Form - Close Virtual Account',
          eventLabel: `virtual_account_id=${virtualaccount.id}`,
        });
      },
      abort: () => {
        window.rzpAnalytics({
          eventCategory: 'Dashboard - Smart Collect',
          eventAction: 'Close Form - Close Virtual Account',
          eventLabel: `virtual_account_id=${virtualaccount.id}`,
        });
      },
    });
  };

  onCopy = virtualaccount => {
    const { closeUrl } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Copy To Clipboard',
        eventLabel: `virtual_account_id${virtualaccount.id}`,
      });
  };

  onTestPaymentModalMount = id => {
    const { closeUrl } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Open Form - Make Test Payment',
        eventLabel: `virtual_account_id=${id}`,
      });
  };

  onTestPaymentModalUnmount = id => {
    const { closeUrl } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Close Form - Make Test Payment',
        eventLabel: `virtual_account_id=${id}`,
      });
  };

  onTestPayment = params => {
    const { closeUrl } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Submit Form - Make Test Payment',
        eventLabel: stringifyQueryParamsWithPipe(params),
      });
  };

  openTestPaymentModal = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <CreateTestPayment
          virtualAccount={this.props.entity}
          onMount={this.onTestPaymentModalMount}
          onUnmount={this.onTestPaymentModalUnmount}
          onTestPayment={this.onTestPayment}
        />
      ),
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
        onCopy={this.onCopy}
      />
    );
  }
}
