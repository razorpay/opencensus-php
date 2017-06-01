import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import TetherComponent from 'react-tether';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import ListContainer from 'merchant/containers/ListContainer';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';
import CreatePaymentLink from 'merchant/containers/Invoices/CreatePaymentLink';
import * as InvoiceActions from 'merchant/modules/invoices/list';
import * as ModalActions from 'rzp/modules/modals';

@withRouter
@connect(
  state => {
    return { ...state.invoices, ...state.session };
  },
  { ...InvoiceActions, ...ModalActions }
)
export default class InvoicesListContainer extends ListContainer {
  fetchEntityList(params) {
    if (this.props.user.tags.indexOf('Newui') !== -1) {
      params.type = 'invoice';
    }

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
            this.props.highLightInvoice(invoice.id);
          }}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  render() {
    let { loading, invoices, user } = this.props;
    let isNewUIEnabled = user.tags.indexOf('Newui') !== -1;
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
              <ShowWhen notMyRole="sellerapp support" featureEnabled="Invoice">
                <NavLink to="/invoices/new" class="btn btn-primary">
                  <i class="icon icon-plus" />
                  <span>Create Invoice</span>
                </NavLink>
              </ShowWhen>

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
          count={this.state.count}
          onSubmit={this.search}
        />

        <Alert type={status.type} message={status.message} />

        <InvoicesList
          invoices={invoices}
          isNewUIEnabled={isNewUIEnabled}
          isLoading={loading}
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
