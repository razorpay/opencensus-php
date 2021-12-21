import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';

import VirtualAccountDetails from './components/Details';
import * as VirtualAccountActions from 'merchant/reducers/virtualaccounts';
import { showNotification } from 'merchant_common/reducers/notifications';
import { openModal } from 'merchant_common/reducers/modals';

import CreateTestPayment from './components/Modals/CreateTestPayment';
import { getKeysSeparatedByPipe, getEventCategoryFromPath } from 'common/utils/rzp-utils';
import moment from 'moment';

@withRouter
@connect(
  (state) => {
    return {
      ...state.virtualaccount,
      mode: state.session.mode,
    };
  },
  {
    openModal,
    showNotification,
    ...VirtualAccountActions,
  },
)
@RTracking(() => window.rzpQ.component('VirtualAccountDetailsContainer'))
export default class VirtualAccountDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentWillMount() {
    const { id } = this.props;
    this.props.fetchItem(id);
    this.props.fetchVAPayments(id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
      this.props.fetchVAPayments(nextProps.id);
    }
  }

  componentDidMount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Open Details - Virtual Account',
        eventLabel: `virtual_account_id=${id}`,
      });

    this.track('open');
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Close Details - Virtual Account',
        eventLabel: `virtual_account_id=${id}`,
      });

    this.track('close');
  }

  track = (event, options) => {
    this.props.tracking.trackEvent(
      window.rzpQ.smartCollect().success(`smartcollect.va.details.${event}`, options),
    );
  };

  closeAccount = (virtualaccount) => {
    this.track('close.initiated');
    this.context.confirm({
      header: 'Close account?',
      message:
        'The account will be closed and your customers will no longer be able to transfer money to this virtual account.',
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () =>
        this.props
          .closeVirtualAccount({ ...virtualaccount, status: 'closed' })
          .then(() => {
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

            this.track('close', { closed: 'yes' });

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

        this.track('close', { closed: 'no' });
      },
    });
  };

  onCopy = (virtualaccount) => {
    const { closeUrl } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Copy To Clipboard',
        eventLabel: `virtual_account_id${virtualaccount.id}`,
      });

    this.track('copy');
  };

  onTestPaymentModalMount = (id) => {
    const { closeUrl } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Open Form - Make Test Payment',
        eventLabel: `virtual_account_id=${id}`,
      });
  };

  onTestPaymentModalUnmount = (id) => {
    const { closeUrl } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Close Form - Make Test Payment',
        eventLabel: `virtual_account_id=${id}`,
      });
  };

  onTestPayment = (params) => {
    const { closeUrl } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Submit Form - Make Test Payment',
        eventLabel: getKeysSeparatedByPipe(params),
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

  updateCloseBy = (data) => {
    const { id } = this.props;
    const close_by = moment(data.expire_by).isValid()
      ? moment(data.expire_by).format('DD-MM-YYYY HH:mm')
      : null;
    const payload = { close_by };

    return this.props
      .updateCloseByDate(id, payload)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Close By updated successfully!',
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { loading, error, entity, va_payments, mode } = this.props;
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
        track={this.track}
        updateCloseByDate={this.updateCloseBy}
      />
    );
  }
}
