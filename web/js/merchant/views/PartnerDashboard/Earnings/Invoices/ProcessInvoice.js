import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import { updateCommissionInvoiceDetail } from 'merchant/reducers/commissionInvoices/details';
import { updateCommissionInvoiceInList } from 'merchant/reducers/commissionInvoices/list';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';
import { initRazorAnalytics } from '@libs/shared-utils';
import { DASHBOARD_TEAMS } from '@libs/shared-types';

class ProcessInvoice extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentDidMount() {
    initRazorAnalytics({ product: DASHBOARD_TEAMS.PARTNERSHIP, user: this.props.user });
  }

  componentWillUnmount() {
    window.razorAnalytics?.disableTracking?.();
  }

  processCommissionInvoice = (commissionInvoice) => {
    const url = `commissions/invoice/${commissionInvoice.id}`;
    const data = {
      action: 'under_review',
    };
    return merchantFetch({ url, method: 'PUT', data });
  };

  handleProcessInvoice = () => {
    this.context.confirm({
      header: 'Are you sure you want to process this invoice?',
      message: () => (
        <div className="text-semi-muted">
          <p>Commission payment process will be initiated for the invoice.</p>
        </div>
      ),
      affirmativeLabel: 'Yes, Process',
      affirmativePendingLabel: 'Requesting...',
      abortLabel: "No, don't!",
      action: async () => {
        const resp = await this.processCommissionInvoice(this.props.commissionInvoice);
        if (resp.success) {
          window.razorAnalytics?.trackStepEvent?.({
            eventName: 'process_invoice_initiated',
          });
          const updatedCommInvoice = {
            ...this.props.commissionInvoice,
            status: 'under_review',
          };
          this.props.updateCommissionInvoiceInList(updatedCommInvoice);
          this.props.updateCommissionInvoiceDetail(updatedCommInvoice);
          this.props.showNotification({
            type: 'success',
            message: 'Commission payment process has been successfully initiated.',
          });
        } else {
          this.props.showNotification({
            type: 'error',
            message: 'Could not submit request to process invoice.',
          });
          window.razorAnalytics?.trackErrorResponse?.({
            eventName: 'process_invoice_initiation_failed',
          });
        }
      },
      abort: () => {},
    });
  };

  render() {
    return (
      <button
        className={`btn btn-primary ${this.props.className || ''}`}
        onClick={this.handleProcessInvoice}
        data-analytics-name="process_invoice"
      >
        Process Invoice
      </button>
    );
  }
}

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    updateCommissionInvoiceInList,
    updateCommissionInvoiceDetail,
    showNotification,
  },
)(ProcessInvoice);
