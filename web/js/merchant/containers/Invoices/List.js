import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import ListContainer from 'merchant/containers/ListContainer';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';
import * as InvoiceActions from 'merchant/modules/invoices/list';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';
import { track } from './ga';
import { merchantFetch } from 'merchant/utils/ajax';

import OnboardingInvoices from './OnboardingInvoices';

@connect(
  state => {
    return { ...state.invoices, ...state.session };
  },
  { ...InvoiceActions }
)
export default class InvoicesListContainer extends ListContainer {
  componentWillMount() {
    super.componentWillMount();

    this.setState({ loadingAllList: true });
    this.fetchAllEntityList();
  }

  componentDidMount() {
    super.componentDidMount();

    // Hotjar tag and events.
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'open_invoice');
      window.hj('tagRecording', ['open_invoice']);
    }
  }

  fetchEntityList(params) {
    params.type = 'invoice';

    return this.props.fetchInvoices(params);
  }

  /* Fetch all payment pages list to find whether first-time user */
  fetchAllEntityList() {
    return merchantFetch({
      url: 'invoices',
      params: {
        count: 1,
        type: 'invoice',
      },
    })
      .then(resp => {
        this.setState({
          loadingAllList: false,
        });

        if (resp.data) {
          this.setState({
            totalInvoicesLength: resp.data.items.length,
          });
        }

        return resp;
      })
      .catch(() => {});
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      track({
        eventAction: 'Search - Invoices',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    track({
      eventAction: 'Clear Search Params - Invoices',
    });
  };

  componentDidMount() {
    track({
      eventAction: 'Go To - Invoices',
    });
  }

  triggerHotjar = () => {
    // Hotjar tag and events.
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'create_invoice');
      window.hj('tagRecording', ['create_invoice']);
    }
  };

  render() {
    let { loading, invoices, user } = this.props;
    let { loadingAllList, totalInvoicesLength, status } = this.state;
    let content;

    if (loadingAllList) {
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (!loadingAllList && !totalInvoicesLength && !invoices.length) {
      // !paymentPages check is required so that while creation first time, the list would be updated while totalPaymentPagesLength still = 0
      content = <OnboardingInvoices />;
    } else {
      content = (
        <React.Fragment>
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
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
            onCopy={({ invoiceId }) => {
              track({
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
        </React.Fragment>
      );
    }
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <ShowWhen
            myRole="admin operations manager owner"
          >
            <div class="btn-toolbar pull-right">
              <NavLink
                to="/invoices/new"
                class="btn btn-primary"
                onClick={this.triggerHotjar}
              >
                <i class="i i-plus" />
                <span>Create Invoice</span>
              </NavLink>
            </div>
          </ShowWhen>
        </HeaderAction>

        {content}
      </div>
    );
  }
}
