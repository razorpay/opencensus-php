import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import ListContainer from 'merchant/containers/ListContainer';
import CreatePaymentLink from 'merchant/containers/Invoices/CreatePaymentLink';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';
import * as InvoiceActions from 'merchant/modules/invoices/list';
import * as ModalActions from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

@connect(state => ({ ...state.invoices, ...state.session }), {
  ...InvoiceActions,
  ...ModalActions,
  luminateRow,
})
export default class PaymentLinksContainer extends ListContainer {
  fetchEntityList(params) {
    params.types = ['link', 'ecod'];
    return this.props.fetchInvoices(params);
  }

  // Temporary fn. for handling code of merchant/models/Invoice.js for handling notes in deserialize fn.
  deserializeNotes(value) {
    let notes = [],
      index = 0;

    for (var key in value) {
      if (value.hasOwnProperty(key)) {
        notes[index] = { key: key, value: value[key] };

        index++;
      }
    }

    return notes;
  }

  showPaymentLinkModal = (invoice = null) => {
    let item = null;
    if (invoice) {
      item = { ...invoice };
      item.notes = this.deserializeNotes(item.notes);
    }

    this.props.openModal({
      component: (
        <CreatePaymentLink
          invoice={item}
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
        eventCategory: 'Dashboard - Payment Links',
        eventAction: 'Search - Payment Links',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payment Links',
      eventAction: 'Clear Search Params - Payment Links',
    });
  };

  onCopy = ({ invoiceId, text }) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payment Links',
      eventAction: 'Copy - Payment Link',
      eventLabel: `payment_link_id=${invoiceId}`,
    });
  };

  render() {
    let { loading, invoices, user } = this.props;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <TestModeBanner />

        <HeaderAction>
          <ShowWhen notMyRole="support">
            <div class="btn-toolbar pull-right">
              <button
                class="btn btn-primary"
                onClick={() => this.showPaymentLinkModal()}
              >
                <i class="icon icon-plus" />
                <span>Create Payment Link</span>
              </button>
            </div>
          </ShowWhen>
        </HeaderAction>

        <InvoiceListFilter
          form="InvoiceListFilter"
          type="link"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <Alert type={status.type} message={status.message} />

        <InvoicesList
          invoices={invoices}
          isLoading={loading}
          type="link"
          onEdit={this.showPaymentLinkModal}
          onCopy={this.onCopy}
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
