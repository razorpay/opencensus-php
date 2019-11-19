import { connect } from 'react-redux';
import { withRouter, NavLink, Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import HeaderAction from 'common/ui/HeaderAction';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import { RZPFeatures } from 'merchant/helpers/data';
import { getKeysSeparatedByPipe, findBy } from 'common/utils/rzp-utils';

import * as InvoiceActions from 'merchant/reducers/invoices/list';
import { fetchReminders } from 'merchant/reducers/reminders';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';

import ListContainer from 'merchant/containers/ListContainer';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';

import { trackSearchFilterForInternational } from './ga';

@withRouter
@connect(state => ({ ...state.invoices, ...state.session }), {
  ...InvoiceActions,
  fetchReminders,
})
@RTracking(() => window.rzpQ.component('PaymentLinksContainer'))
export default class PaymentLinksContainer extends ListContainer {
  componentDidMount() {
    this.props.fetchReminders();
  }

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

  onDuplicate = invoiceId => {
    this.props.history.push(`/paymentlinks/new?duplicate_id=${invoiceId}`);
  };

  render() {
    let { loading, invoices, user, mode, tracking } = this.props;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            {user.isRemindersEnabled && (
              <span class="btn btn-link">
                <span class="badge bg-success m-r">new</span>

                <Link to="/reminders">Reminder Settings</Link>
              </span>
            )}

            <TakeATourButton feature={RZPFeatures.PL} />

            <DocsLink url="https://razorpay.com/docs/payment-links/" />

            <ShowWhen
              additionalCondition={user =>
                (mode !== 'live' || !user.isRejected) &&
                user.isAllowedEdit('payment_links')
              }
            >
              <NavLink class="btn btn-primary" to="/paymentlinks/new">
                <i class="i i-plus" />
                <span
                  onClick={() =>
                    tracking.trackEvent(
                      window.rzpQ.onbr().success('dash.pl_action', {
                        action: 'Initiate_PL_Creation',
                      })
                    )
                  }
                >
                  Create Payment Link
                </span>
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
          trackSearchFilterForInternational={trackSearchFilterForInternational}
        />

        <Alert type={status.type} message={status.message} />

        <InvoicesList
          invoices={invoices}
          isLoading={loading}
          type="link"
          onCopy={this.onCopy}
          onDuplicate={this.onDuplicate}
          EmptyList={EmptyComponent}
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

// TODO: Update colSpan if no of columns are changes
const EmptyComponent = () => (
  <EmptyListWithTableRow
    colSpan={8}
    description={
      <React.Fragment>
        <div>There are no payment links yet!!</div>
        <div>Start creating new links now.</div>
      </React.Fragment>
    }
  />
);
