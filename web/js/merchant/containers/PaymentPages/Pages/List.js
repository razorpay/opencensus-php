import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { NavLink } from 'react-router-dom';
import { withRouter } from 'react-router-dom';

import { RZPFeatures } from 'rzp/utils/constants';
import RTracking from 'react-tracking';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import Spinner from 'rzp/ui/Spinner';
import ListContainer from 'merchant/containers/ListContainer';
import ListFilter from 'merchant/components/ListFilter';
import { fetchPaymentPagesList } from './model';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

import Time from 'rzp/ui/Time';
import Pager from 'rzp/ui/Pager';
import Amount from 'rzp/ui/Amount';
import Spinner from 'rzp/ui/Spinner';
import TableBody from 'rzp/ui/TableBody';
import HeaderAction from 'rzp/ui/HeaderAction';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';

import { showNotification } from 'rzp/modules/notifications';

import ShowWhen from 'merchant/components/ShowWhen';
import EmptyList from 'merchant/components/EmptyList';
import ListFilter from 'merchant/components/ListFilter';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';

import { populateRPLReduxList } from 'merchant/modules/invoices/list';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import ListContainer from 'merchant/containers/ListContainer';
import EntityItemRow from 'merchant/containers/EntityItemRow';

import {
  getIsPaymentPagesEnabled,
  getIsAllowedPaymentPagesResetOnBoarding,
} from '../OnBoarding';
import { getPaymentPageQuickGuideIsClosed } from '../QuickGuide';

import { trackListActions } from './ga';
import { fetchPaymentPagesList } from './model';

@withRouter
@connect(
  state => ({
    ...state.invoices,
    ...state.session,
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.PP
    ),
  }),
  {
    showNotification,
    populateRPLReduxList,
    handleProductQuickGuide,
  }
)
export default class PaymentPagesContainer extends ListContainer {
  state = {
    loading: true,
    loadingAllList: true,
  };

  componentDidMount() {
    this.fetchAllEntityList();
    this.initPaymentPagesOnboarding();
  }

  componentWillReceiveProps(nextProps, nextState) {
    if (this.props.paymentPages.length !== nextProps.paymentPages.length) {
      const newLength = nextProps.paymentPages.length;

      if (newLength) {
        this.setState({
          totalPaymentPagesLength: newLength,
        });
      }
    }

    if (nextProps.loading !== this.props.loading) {
      this.initPaymentPagesOnboarding(nextProps);
    }

    super.componentWillReceiveProps(nextProps);
  }

  /* Fetch all payment pages list to find whether first-time user */
  fetchAllEntityList() {
    fetchPaymentPagesList({
      count: 1,
    })
      .then(resp => {
        this.setState({
          loadingAllList: false,
        });

        if (resp.data) {
          this.setState({
            totalPaymentPagesLength: resp.data.items.length,
          });
        }

        this.initPaymentPagesOnboarding();

        return resp;
      })
      .catch(() => {});
  }

  fetchEntityList(params) {
    return fetchPaymentPagesList(params)
      .then(resp => {
        if (resp.data) {
          this.props.populateRPLReduxList(resp);
        }

        this.setState({ loading: false });

        this.initPaymentPagesOnboarding();

        return resp;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.setState({ loading: false });
      });
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      trackListActions('Search', label);
    }
  };

  onClearAnalytics = () => {
    trackListActions('Clear');
  };

  componentWillUnmount() {
    const { paymentPageProductOnBoarding } = this.props;

    if (paymentPageProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...paymentPageProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: this.state.isPaymentPageWysiwyg,
        isTour: this.state.isPaymentPageWysiwyg,
      });
    }
  }

  handleProductQuickGuide = () => {
    this.setState(
      {
        isPaymentPageWysiwyg: true,
      },
      () => {
        this.props.history.push('/paymentpages/new');
      }
    );
  };

  initPaymentPagesOnboarding = (props = this.props) => {
    if (props.paymentPageProductOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      paymentPages: props.paymentPages,
      loading: this.state.loading || this.state.loadingAllList,
    };

    const isPaymentPagesEnabled = getIsPaymentPagesEnabled(data);

    let showOnboarding = !isPaymentPagesEnabled;

    if (isPaymentPagesEnabled) {
      showOnboarding = getIsAllowedPaymentPagesResetOnBoarding(data);
    }

    let paymentPageProductOnBoarding = {
      ...props.paymentPageProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getPaymentPageQuickGuideIsClosed(props),
    };

    this.props.handleProductQuickGuide(paymentPageProductOnBoarding);
  };

  render() {
    const { loading, loadingAllList, totalPaymentPagesLength } = this.state;
    const { paymentPages, user, tracking } = this.props;

    const isRoleAllowedEdit = user.isAllowedEdit('payment_pages');

    let content;

    if (loadingAllList) {
      content = (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    } else if (
      isRoleAllowedEdit &&
      !loadingAllList &&
      !totalPaymentPagesLength &&
      !paymentPages.length
    ) {
      // !paymentPages check is required so that while creation first time, the list would be updated while totalPaymentPagesLength still = 0
      content = <EmptyComponent />;
    } else {
      content = (
        <React.Fragment>
          <ListFilter
            form="PaymentPagesPaymentListFilter"
            count={this.state.count}
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
          >
            <div class="form-group list-filter-item">
              <label>Title</label>
              <Field
                name="title"
                component="input"
                class="form-control input-sm"
              />
            </div>

            <div class="form-group list-filter-item">
              <label>Status</label>
              <Field
                name="status"
                component="select"
                class="form-control input-sm"
              >
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </Field>
            </div>

            <div class="form-group list-filter-item count">
              <label>Count</label>
              <Field
                name="count"
                component="input"
                min={1}
                max={100}
                type="number"
                class="form-control input-sm"
              />
            </div>
          </ListFilter>
          <div class="table-responsive">
            <table class="table table-hover table-striped">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Amount</th>
                  <th>Available Quantity</th>
                  <th>Total Sales</th>
                  <th>Page Url</th>
                  <th>Created At</th>
                  <th>Status</th>
                </tr>
              </thead>
              <TableBody
                isLoading={loading}
                colSpan={8}
                rows={paymentPages}
                emptyTableMsg="No data found!"
              >
                {paymentPages.map(item => (
                  <EntityItemRow id={item.id} key={item.id}>
                    <td>
                      <NavLink
                        to={`/paymentpages/${item.id}`}
                        onClick={() => {
                          trackListActions('Title Click');
                        }}
                      >
                        {item.title}
                      </NavLink>
                    </td>
                    <td class="text-right">
                      {item.amount ? (
                        <Amount value={item.amount} currency={item.currency} />
                      ) : (
                        '--'
                      )}
                    </td>
                    <td>
                      {item.times_payable
                        ? Number(item.times_payable) -
                          Number(item.times_paid) +
                          '/' +
                          Number(item.times_payable)
                        : 'No Limit'}
                    </td>

                    <td className="text-right">
                      <Amount
                        value={item.total_amount_paid}
                        currency={item.currency}
                      />
                    </td>
                    <td>
                      {item.short_url && (
                        <span class="CopyLink">
                          <span>{item.short_url}</span>
                          <CustomClipboard
                            value={item.short_url}
                            onCopy={() => {
                              trackListActions('Click Copy URL');
                            }}
                          >
                            <button class="btn btn-default btn-xs">copy</button>
                          </CustomClipboard>
                        </span>
                      )}
                    </td>
                    <td>
                      <Time value={item.created_at} />
                    </td>
                    <td>
                      <PaymentPagesStatusLabel status={item.status} />
                    </td>
                  </EntityItemRow>
                ))}
              </TableBody>
            </table>
          </div>
          {!loading &&
            !!paymentPages.length && (
              <Pager
                count={this.state.count}
                skip={this.state.skip}
                length={paymentPages.length}
                onClick={this.paginate}
              />
            )}
        </React.Fragment>
      );
    }

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <TakeATourButton feature={RZPFeatures.PP} />

            <ShowWhen
              additionalCondition={user =>
                user.isOrgAllowedFunctionality('external_links')
              }
            >
              <a
                class="btn btn-link settlement-doc-btn"
                href="https://razorpay.com/docs/payment-pages/"
                target="_blank"
              >
                Documentation&nbsp;<span class="icon i-external-link" />
              </a>
            </ShowWhen>

            {isRoleAllowedEdit && (
              <span
                class="btn btn-primary"
                onClick={this.handleProductQuickGuide}
              >
                <i class="i i-plus" />
                <span
                  onClick={() => {
                    tracking.trackEvent(
                      window.rzpQ.onbr().success('dash.pp_action', {
                        action: 'Initiate_PP_Creation',
                      })
                    );
                  }}
                >
                  Create Payment Page
                </span>
              </span>
            )}
          </div>
        </HeaderAction>

        {content}
      </div>
    );
  }
}

const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no payment pages yet!!</div>
        <div>Start creating new links now.</div>
      </React.Fragment>
    }
  />
);
