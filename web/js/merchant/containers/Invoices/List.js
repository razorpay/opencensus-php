import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';

import { RZPFeatures } from 'rzp/utils/constants';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

import Pager from 'rzp/ui/Pager';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import HeaderAction from 'rzp/ui/HeaderAction';

import { merchantFetch } from 'merchant/utils/ajax';
import * as InvoiceActions from 'merchant/modules/invoices/list';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import InvoicesList from 'merchant/components/Invoices/InvoicesList';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter';

import ListContainer from 'merchant/containers/ListContainer';

import { track } from './ga';

import OnboardingInvoices from './OnboardingInvoices';

@withRouter
@connect(
  state => {
    return {
      ...state.invoices,
      ...state.session,
      invoicesProductOnBoarding: getCurrentProductOnBoardingDetails(
        state,
        RZPFeatures.INVOICE
      ),
    };
  },
  { ...InvoiceActions, handleProductQuickGuide }
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
    params.subscriptions = '0';
    params.type = 'invoice';

    return this.props.fetchInvoices(params);
  }

  onDuplicate = invoiceId => {
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

  componentWillUnmount() {
    const { invoicesProductOnBoarding } = this.props;

    if (invoicesProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...invoicesProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: this.state.isInvoiceView,
        isTour: this.state.isInvoiceView,
      });
    }
  }

  onClickNewInvoice = () => {
    const { history } = this.props;

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
      }
    );
  };

  render() {
    let { loading, invoices, user, mode } = this.props;
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
            isInttCurrenciesEnabled={user.isInttCurrenciesEnabled}
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
            <TakeATourButton feature={RZPFeatures.INVOICE} />

            <DocsLink url="https://razorpay.com/docs/invoices/" />

            <ShowWhen
              additionalCondition={user =>
                (mode !== 'live' || !user.isRejected) &&
                user.isAllowedEdit('invoices')
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
