import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import ListContainer from 'merchant/containers/ListContainer';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';
import CreatePaymentLink from 'merchant/containers/Invoices/CreatePaymentLink';
import * as InvoiceActions from 'merchant/modules/invoices/list';
import * as ModalActions from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

@withRouter
@connect(
  state => {
    return { ...state.invoices, ...state.session };
  },
  { ...InvoiceActions, ...ModalActions, luminateRow }
)
export default class InvoicesListContainer extends ListContainer {
  fetchEntityList(params) {
    params.type = 'invoice';

    return this.props.fetchInvoices(params);
  }

  editInvoice = invoice => {
    if (invoice.type === 'link') {
      this.showPaymentLinkModal(invoice);
    } else {
      this.props.history.push(`/invoices/${invoice.id}`);
    }
  };

  showPaymentLinkModal = (invoice = null) => {
    this.props.openModal({
      component: (
        <CreatePaymentLink
          invoice={invoice}
          onSave={invoice => {
            this.props.luminateRow(invoice.id);
          }}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  onSearchAnalytics = params => {
    const label = stringifyQueryParamsWithPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Invoices',
        eventAction: 'Search - Invoices',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Invoices',
      eventAction: 'Clear Search Params - Invoices',
    });
  };

  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Invoices',
      eventAction: 'Go To - Invoices',
    });
  }

  render() {
    let { loading, invoices, user } = this.props;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <ShowWhen notMyRole="support">
            <div class="btn-toolbar pull-right">
              <ShowWhen notMyRole="sellerapp support" featureEnabled="Invoice">
                <NavLink to="/invoices/new" class="btn btn-primary">
                  <i class="icon icon-plus" />
                  <span>Create Invoice</span>
                </NavLink>
              </ShowWhen>
            </div>
          </ShowWhen>
        </HeaderAction>

        <InvoiceListFilter
          form="InvoiceListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <Alert type={status.type} message={status.message} />

        <InvoicesList
          invoices={invoices}
          isLoading={loading}
          onEdit={this.editInvoice}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
          onCopy={({ invoiceId }) => {
            window.rzpAnalytics({
              eventCategory: 'Dashboard - Invoices',
              eventAction: 'Copy - Invoice Link',
              eventLabel: `invoice_id=${invoiceId}`,
            });
          }}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={invoices.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
