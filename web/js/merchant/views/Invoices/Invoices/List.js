import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import Pager from 'common/ui/Pager';
import Spinner from 'common/ui/Spinner';
import React from 'react';
import Alert from 'common/ui/Forms/Alert';
import HeaderAction from 'common/ui/HeaderAction';

import { merchantFetch } from 'merchant/utils/ajax';
import * as InvoiceActions from 'merchant/reducers/invoices/list';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import InvoicesList from 'merchant/views/Invoices/Invoices/components/List';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import InvoiceListFilter from 'merchant/views/Invoices/Invoices/components/ListFilter';
import EmptyList from 'merchant/components/EmptyList';

import ListContainer from 'merchant/containers/ListContainer';

import { track, trackSearchFilterForInternational } from '../ga';

@withRouter
@connect(
  (state) => {
    return {
      ...state.invoices,
      ...state.session,
      invoicesProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.INVOICE),
    };
  },
  { ...InvoiceActions, handleProductQuickGuide },
)
export default class InvoicesListContainer extends ListContainer {
  UNSAFE_componentWillMount() {
    super.UNSAFE_componentWillMount();

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
    params.subscriptions = '0';
    params.type = 'invoice';

    return this.props.fetchInvoices(params);
  }

  onDuplicate = (invoiceId) => {
    this.props.history.push(`/invoices/new?duplicate_id=${invoiceId}`);
  };

  /* Fetch all payment pages list to find whether first-time user */
  fetchAllEntityList() {
    return merchantFetch({
      url: 'invoices',
      params: {
        count: 1,
        type: 'invoice',
        subscriptions: '0',
      },
    })
      .then((resp) => {
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

  onSearchAnalytics = (params) => {
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

  // eslint-disable-next-line no-dupe-class-members
  componentDidMount() {
    track({
      eventAction: 'Go To - Invoices',
    });
  }

  componentWillUnmount() {
    const { invoicesProductOnBoarding } = this.props;

    if (invoicesProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...invoicesProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: this.state.isInvoiceView,
        isTour: this.state.isInvoiceView,
        lastItemId: null,
      });
    }
  }

  onClickNewInvoice = () => {
    const { history, invoices, invoicesProductOnBoarding } = this.props;

    if (invoicesProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...invoicesProductOnBoarding,
        lastElementId: (invoices[0] || {}).id,
      });
    }

    this.setState(
      {
        isInvoiceView: true,
      },
      () => {
        history.push('/invoices/new');

        // Hotjar tag and events.
        if (typeof window.hj === 'function') {
          window.hj('trigger', 'create_invoice');
          window.hj('tagRecording', ['create_invoice']);
        }
      },
    );
  };

  render() {
    const { loading, invoices, user, mode } = this.props;
    const { loadingAllList, totalInvoicesLength, status } = this.state;
    let content;

    if (loadingAllList) {
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (!loadingAllList && !totalInvoicesLength && !invoices.length) {
      // !paymentPages check is required so that while creation first time, the list would be updated while totalPaymentPagesLength still = 0
      content = (
        <EmptyList
          description={
            <React.Fragment>
              <div>There are no invoices yet!!</div>
              <div>Start creating new invoices now.</div>
            </React.Fragment>
          }
        />
      );
    } else {
      content = (
        <React.Fragment>
          <InvoiceListFilter
            form="InvoiceListFilter"
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
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
            onCopy={({ invoiceId }) => {
              track({
                eventAction: 'Copy - Invoice Link',
                eventLabel: `invoice_id=${invoiceId}`,
              });
            }}
            onDuplicate={this.onDuplicate}
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
          <div class="btn-toolbar pull-right">
            <ShowWhen additionalCondition={(_user) => !_user.isOrgAxis}>
              <TakeATourButton feature={RZPFeatures.INVOICE} />
            </ShowWhen>

            <DocsLink url="https://razorpay.com/docs/invoices/" />

            <ShowWhen
              additionalCondition={(_user) =>
                (mode !== 'live' || !_user.isRejected) && _user.isAllowedEdit('invoices')
              }
            >
              <span class="btn btn-primary" onClick={this.onClickNewInvoice}>
                <i class="i i-plus" />
                <span>Create Invoice</span>
              </span>
            </ShowWhen>
          </div>
        </HeaderAction>

        {content}
      </div>
    );
  }
}
