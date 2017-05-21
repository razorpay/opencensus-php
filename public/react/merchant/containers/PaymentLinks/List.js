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
import PaymentLinkDetails from 'merchant/containers/PaymentLinks/Details';
import * as InvoiceActions from 'merchant/modules/invoices/list';
import * as ModalActions from 'rzp/modules/modals';
import { openSlider } from 'rzp/modules/slider';

@connect(state => state.invoices, {
  ...InvoiceActions,
  ...ModalActions,
  openSlider,
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

  showPaymentLinkDetails = invoice => {
    this.props.openSlider({
      component: <PaymentLinkDetails id={invoice.id} />,
      onOpenURL: `/app/paymentlinks/${invoice.id}`,
      onCloseURL: '/app/paymentlinks',
    });
  };

  render() {
    let { loading, invoices } = this.props;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <TetherComponent
          target="#link-header"
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
          highlightRow={invoice => invoice.id === this.props.highLightInvoiceId}
          onEdit={this.showPaymentLinkModal}
          onInvoiceClick={this.showPaymentLinkDetails}
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
