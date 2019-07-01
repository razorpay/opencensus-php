import { connect } from 'react-redux';
import { NavLink, Link } from 'react-router-dom';

import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

import Alert from 'rzp/ui/Forms/Alert';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';

import * as InvoiceActions from 'merchant/modules/invoices/list';

import DocsLink from 'merchant/components/DocsLink';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';
import ShowWhen from 'merchant/components/ShowWhen';

import ListContainer from 'merchant/containers/ListContainer';

@connect(state => ({ ...state.invoices, ...state.session }), {
  ...InvoiceActions,
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

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);

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
    let { loading, invoices, user, mode } = this.props,
      { status } = this.state;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <span class="btn btn-link">
              <span class="badge bg-success m-r">new</span>

              <Link to="/reminders">Reminder Settings</Link>
            </span>

            <DocsLink url="https://razorpay.com/docs/payment-links/" />

            <ShowWhen
              additionalCondition={user =>
                (mode !== 'live' || !user.isRejected) &&
                user.isAllowedEdit('payment_links')
              }
            >
              <NavLink class="btn btn-primary" to="/paymentlinks/new">
                <i class="i i-plus" />
                <span>Create Payment Link</span>
              </NavLink>
            </ShowWhen>
          </div>
        </HeaderAction>

        <InvoiceListFilter
          form="InvoiceListFilter"
          type="link"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
          isInttCurrenciesEnabled={user.isInttCurrenciesEnabled}
        />

        <Alert type={status.type} message={status.message} />

        <InvoicesList
          invoices={invoices}
          isLoading={loading}
          type="link"
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
