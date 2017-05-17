import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import TetherComponent from 'react-tether';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import ListContainer from 'merchant/containers/ListContainer';
import CreatePaymentLink from './CreatePaymentLink';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';
import * as InvoiceActions from 'merchant/modules/invoices/list';
import * as ModalActions from 'rzp/modules/modals';

@connect(state => state.invoices, { ...InvoiceActions, ...ModalActions })
export default class InvoicesListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchInvoices(params);
  }

  showPaymentLinkModal(invoice = null) {
    this.props.openModal({
      component: (
        <CreatePaymentLink
          invoice={invoice}
          onSave={invoice => {
            this.props.highLightInvoice(invoice.id);
          }}
          closeModal={this.props.closeModal}
        />
      ),
    });
  }

  editInvoice = invoice => {
    if (invoice.type === 'link') {
      this.showPaymentLinkModal(invoice);
    } else if (invoice.type === 'invoice') {
      location.hash = `#/app/invoices/${invoice.id}`;
    }
  };

  render() {
    let { loading, invoices } = this.props;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <TetherComponent
          target="#invoicing-header"
          attachment="top right"
          targetAttachment="top right"
          offset="-8px 20px"
        >
          <div />{/* required by react-tether */}

          <ShowWhen notMyRole="support">
            <div class="btn-toolbar pull-right">
              <button
                class="btn btn-primary btn-rounded"
                onClick={() => this.showPaymentLinkModal()}
              >
                <i class="fa fa-plus" />
                <span>Create Payment Link</span>
              </button>

              <ShowWhen notMyRole="sellerapp" featureEnabled="Invoice">
                <a
                  href="#/app/invoices/new"
                  class="btn btn-primary btn-rounded"
                >
                  <i class="fa fa-plus" />
                  <span>Create Invoice</span>
                </a>
              </ShowWhen>
            </div>
          </ShowWhen>
        </TetherComponent>

        <InvoiceListFilter
          form="InvoiceListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <Alert type={status.type} message={status.message} />

        <InvoicesList
          invoices={invoices}
          isLoading={loading}
          highlightRow={invoice => invoice.id === this.props.highLightInvoiceId}
          onEdit={this.editInvoice}
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
