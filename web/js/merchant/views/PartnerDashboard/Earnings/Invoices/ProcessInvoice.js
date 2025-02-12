import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import { updateCommissionInvoiceDetail } from 'merchant/reducers/commissionInvoices/details';
import { updateCommissionInvoiceInList } from 'merchant/reducers/commissionInvoices/list';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';

class ProcessInvoice extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

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
      >
        Process Invoice
      </button>
    );
  }
}

export default connect(null, {
  updateCommissionInvoiceInList,
  updateCommissionInvoiceDetail,
  showNotification,
})(ProcessInvoice);
