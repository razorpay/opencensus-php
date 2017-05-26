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
import * as InvoiceActions from 'merchant/modules/invoices/list';
import * as ModalActions from 'rzp/modules/modals';

@withRouter
@connect(state => state.invoices, { ...InvoiceActions, ...ModalActions })
export default class InvoicesListContainer extends ListContainer {
  fetchEntityList(params) {
    params.type = 'invoice';
    return this.props.fetchInvoices(params);
  }

  editInvoice = invoice => {
    this.props.history.push(`/invoices/${invoice.id}`);
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
          <ShowWhen notMyRole="sellerapp support" featureEnabled="Invoice">
            <NavLink to="/invoices/new" class="btn btn-primary pull-right">
              <i class="icon icon-plus" />
              <span>Create Invoice</span>
            </NavLink>
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
