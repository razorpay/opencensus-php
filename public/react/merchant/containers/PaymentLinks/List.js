import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import TetherComponent from 'react-tether';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import ListContainer from 'merchant/containers/ListContainer';
import CreatePaymentLink from 'merchant/containers/Invoices/CreatePaymentLink';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';
import * as InvoiceActions from 'merchant/modules/invoices/list';
import * as ModalActions from 'rzp/modules/modals';

@connect(state => state.invoices, {
  ...InvoiceActions,
  ...ModalActions,
})
export default class PaymentLinksContainer extends ListContainer {
  fetchEntityList(params) {
    params.type = 'link';
    return this.props.fetchInvoices(params);
  }

  showPaymentLinkModal = (invoice = null) => {
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
  };

  render() {
    let { loading, invoices } = this.props;
    let status = this.state.status;

    return (
      <tabbed-container>
        <header id="link-header">
          <NavLink to="/paymentlinks">Payment Links</NavLink>
        </header>

        <div class="content-wrapper">
          <TetherComponent
            target="#link-header"
            attachment="top right"
            targetAttachment="top right"
            offset="-8px 0"
          >
            <div />{/* required by react-tether */}

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
          </TetherComponent>

          <InvoiceListFilter
            form="InvoiceListFilter"
            type="link"
            count={this.state.count}
            onSubmit={this.search}
          />

          <Alert type={status.type} message={status.message} />

          <InvoicesList
            invoices={invoices}
            isLoading={loading}
            type="link"
            onEdit={this.showPaymentLinkModal}
          />

          <Pager
            count={this.state.count}
            skip={this.state.skip}
            length={invoices.length}
            onClick={this.paginate}
          />
        </div>
      </tabbed-container>
    );
  }
}
