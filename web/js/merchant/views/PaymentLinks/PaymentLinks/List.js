import { connect } from 'react-redux';
import { NavLink, Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';
import { Field } from 'redux-form';
import { withI18Service } from 'common/i18';
import TestModeBanner from 'merchant/components/TestModeBanner';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import {
  getKeysSeparatedByPipe,
  getCommonAnalyticsProperties,
  getURLQueryParams,
} from 'common/utils/rzp-utils';
import moment from 'moment';
import { analyticsTrack } from 'common/utils/analytics';
import ProductWrapper from 'common/ui/ProductWrapper';
import { fetchPaymentLinks } from 'merchant/reducers/paymentlinks/list';
import { fetchReminders } from 'merchant/reducers/reminders';
import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import Popover, { PopoverBody } from 'common/ui/Popover';
import List from 'merchant/views/Invoices/Invoices/components/List';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { RZPFeatures } from 'merchant/helpers/data';
import ListFilter from 'merchant/views/Invoices/Invoices/components/ListFilter';
import DateRangePicker from 'common/ui/DateRangePicker';
import ListContainer from 'merchant/containers/ListContainer';
import EmptyList from 'merchant/components/EmptyList';
import track from './track';
import { trackSearchFilterForInternational } from './ga';
import EasterEgg from 'merchant/components/EasterEgg';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { isReminderEnabled } from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { PRODUCTS_DATA } from 'merchant/components/SidebarV2/utils/Products';
const {
  accountsettings: { additionalCondition: isAccountsAndSettingsEnabled },
} = PRODUCTS_DATA;
// TODO: Update colSpan if no of columns are changes
const EmptyComponent = () => (
  <EmptyList
    description={
      <>
        <div>There are no payment links yet!!</div>
        <div>Start creating new links now.</div>
      </>
    }
  />
);

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

const isOutsideRange = (day) => {
  return day.isBefore(moment().subtract(18, 'months'));
};

const getExtraFields = (user, tracking, onDatesChange) => {
  const fields = [];

  if (user.isPaymentLinkCreationV2Enabled && user.isCountryIndia) {
    fields.push(
      <div key="upi_link" class="form-group list-filter-item">
        <label>Payment Link Type</label>
        <Field
          name="upi_link"
          component="select"
          class="form-control input-sm"
          onChange={(event) => {
            tracking.trackEvent(
              window.rzpQ.paymentLinks().interaction(`pl.browse.link_type`, {
                selection: event.target.name,
              }),
            );
          }}
        >
          <option value="">All Types</option>
          <option name="standard" value="0">
            Standard Payment Link
          </option>
          <option name="upi" value="1">
            UPI Payment Link
          </option>
        </Field>
      </div>,
    );
  }

  fields.push(
    <div key="duration" class="form-group datepicker-group">
      <label>Duration</label>
      <DateRangePicker
        isOutsideRange={isOutsideRange}
        presets={dateRangePresets}
        onDatesChange={onDatesChange}
        renderCalendarInfo={() => (
          <div className="PaymentLinks--Calender-Info">
            <div className="PaymentLinks--Archive--Banner">
              <i className="i i-info-circle icon-wrapper" />
              To search for payment links older than 18 months, please
              <Link to="/reports"> download a report </Link>& search through it.
            </div>
          </div>
        )}
      />
    </div>,
  );

  return fields;
};
@connect((state) => ({ ...state.paymentlinks, ...state.session }), {
  fetchPaymentLinks,
  fetchReminders,
})
@RTracking(() => window.rzpQ.component('PaymentLinksContainer'))
class PaymentLinksContainer extends ListContainer {
  constructor(props) {
    super(props);

    const isVisible =
      props.user.isAllowedView('payment_links_batch_uploads') &&
      props.user.isPLBatchUploadEnabled &&
      (!props.user.isSellerAppRole || props.user.isPaymentLinkBatchEnabledForSellerAppRole);
    this.state = {
      ...this.state,
      date: { from: '', to: '' },
      tabsData: [
        { title: 'Payment Links', url: '/paymentlinks' },
        {
          title: 'Batch Uploads',
          url: '/paymentlinks/batchuploads',
          hidden: !isVisible,
        },
      ],
    };
  }

  componentDidMount() {
    this.props.fetchReminders();
    track.init(this.props.tracking.trackEvent);
    const params = getURLQueryParams(this.props.location.search);
    if (params?.link_type) {
      setTimeout(() => {
        this.props.history.push(`/paymentlinks/new?link_type=${params.link_type}`);
        this.props.tracking.trackEvent(window.rzpQ.onbr().initiated('payment_link_popup_loaded'));
      }, 1200);
    }
  }

  fetchEntityList(params) {
    params.types = ['link', 'ecod'];
    // exclude missed orders PLs as they will be displayed on a separate tab
    params.source_not_in = 'missed_orders_plink';
    return this.props.fetchPaymentLinks(params);
  }

  // Temporary fn. for handling code of merchant/models/Invoice.js for handling notes in deserialize fn.
  deserializeNotes(value) {
    const notes = [];
    let index = 0;

    for (const key in value) {
      if (value.hasOwnProperty(key)) {
        notes[index] = { key, value: value[key] };

        index++;
      }
    }

    return notes;
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);

    if (label && label.length > 0) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Payment Links',
        eventAction: 'Search - Payment Links',
        eventLabel: label,
      });
    }
    track.searchSubmit();
    track.searchStatus(label);
    Object.keys(params).forEach((param) => {
      this.props.tracking.trackEvent(
        window.rzpQ.paymentLinks().interaction('pl.search.status', {
          origin: 'dashboard',
          modified: this.searchFilters[param] !== params[param],
        }),
      );
      switch (param) {
        case 'international':
          return track.searchCurrency();
        case 'count':
          return track.searchCount();
        default:
          return false;
      }
    });
  };

  onClearAnalytics = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payment Links',
      eventAction: 'Clear Search Params - Payment Links',
    });

    track.clearSearch();
  };

  onCopy = ({ invoiceId, _text }) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payment Links',
      eventAction: 'Copy - Payment Link',
      eventLabel: `payment_link_id=${invoiceId}`,
    });
  };

  onDuplicate = (invoiceId) => {
    this.props.history.push(`/paymentlinks/new?duplicate_id=${invoiceId}`);
  };

  onAlertCloseClick = () => {
    track.searchError(this.state.status.message[1]);
  };

  onDatesChange = (from, to) => {
    const date = {
      from: from.unix(),
      to: to.unix(),
    };

    this.setState({
      date,
    });
  };

  trackTourClick = () => {
    track.onNeedHelp(RZPFeatures.PL);
  };

  trackDocumentClick = () => {
    track.onDocumentClick(RZPFeatures.PL);
  };

  trackReminderSetting = () => {
    track.onReminderSettingClick(RZPFeatures.PL);
  };

  render() {
    const { loading, paymentlinks, user: users, mode, tracking } = this.props;
    const status = this.state.status;
    const extraConfig = {
      abExperiments: {},
      isConfigTagEnabled: this.props.i18.isConfigTagEnabled,
    };

    const docsLinkProps = {
      url: 'https://razorpay.com/docs/payment-links/',
    };

    if (users.isPaymentlinksV2Enabled) {
      docsLinkProps.url = users.isPaymentlinksV2CompatEnabled
        ? 'https://razorpay.com/docs/api/payment-links/v1/'
        : 'https://razorpay.com/docs/payments/payment-links/create/?utm_source=razorpay-dashboard&utm_medium=docs-link&utm_campaign=dash-exp';
      docsLinkProps.title = (
        <span>
          Documentation <span class="badge bg-success m-r hidden-xs">new</span>
          <Popover theme="dark" parentQuerySelector=".tether-element">
            <PopoverBody>
              New API Contract is applicable for your <br /> merchant profile
            </PopoverBody>
          </Popover>
        </span>
      );
    }
    const { tabsData } = this.state;
    return (
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <>
            <ShowWhen
              additionalCondition={(user) =>
                isReminderEnabled(extraConfig) && isAccountsAndSettingsEnabled(user)
              }
            >
              <span class="btn btn-link">
                <span class="badge bg-success m-r hidden-xs">new</span>

                <Link
                  to="/payments-and-refunds-settings/reminders"
                  onClick={this.trackReminderSetting}
                >
                  Reminder Settings
                </Link>
              </span>
            </ShowWhen>
            <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
              <TakeATourButton feature={RZPFeatures.PL} />
            </ShowWhen>

            <ShowWhen
              additionalCondition={() =>
                !this.props?.i18?.isConfigTagEnabled('documentation.documentation')
              }
            >
              <span className="hidden-xs">
                <DocsLink {...docsLinkProps} onClick={this.trackDocumentClick} />
              </span>
              <div className="mob-header hidden-lg">
                <DocsLink {...docsLinkProps} onClick={this.trackDocumentClick} />
                <span className="badge bg-success m-r hidden-lg">new</span>
              </div>
            </ShowWhen>

            <ShowWhen
              additionalCondition={(user) =>
                (mode !== 'live' || !user.isRejected) && user.isAllowedEdit('payment_links')
              }
            >
              <span className="tabbed-header-actions">
                <span className="cta-container">
                  <NavLink className="btn btn-primary btn-shine" to="/paymentlinks/new">
                    <i class="i i-plus" />
                    <span
                      onClick={() => {
                        tracking.trackEvent(
                          window.rzpQ.onbr().success('dash.pl_action', {
                            action: 'Initiate_PL_Creation',
                          }),
                        );
                        selfServeTrackInitiate({
                          selfServeAction: 'Create Payment Link',
                          page: 'Paymentlink',
                          screen: 'Payment Links',
                        });
                        analyticsTrack({
                          objectName: 'create payment link',
                          actionName: 'clicked',
                          screen: 'create payment link',
                          properties: {
                            ...getCommonAnalyticsProperties(window.rzp_user),
                          },
                        });
                      }}
                    >
                      Create Payment Link
                    </span>
                  </NavLink>
                </span>
              </span>
            </ShowWhen>
          </>
        }
      >
        <content>
          <div class="content-wrapper">
            <TestModeBanner />
            <ListFilter
              form="InvoiceListFilter"
              type="link"
              count={this.state.count}
              date={this.state.date}
              onSubmit={this.search}
              onSearchAnalytics={this.onSearchAnalytics}
              onClearAnalytics={this.onClearAnalytics}
              isInttCurrenciesEnabled={users.isInttCurrenciesEnabled}
              trackSearchFilterForInternational={trackSearchFilterForInternational}
              isPaymentlinksV2Enabled={users.isPaymentlinksV2Enabled}
              extraFields={getExtraFields(users, this.props.tracking, this.onDatesChange)}
              user={users}
            />

            <Alert
              type={status.type}
              message={status.message}
              onCloseClick={this.onAlertCloseClick}
            />

            <List
              invoices={paymentlinks}
              isLoading={loading}
              type="link"
              onCopy={this.onCopy}
              onDuplicate={this.onDuplicate}
              EmptyList={EmptyComponent}
              isPaymentlinksV2Enabled={users.isPaymentlinksV2Enabled}
              onShareLinkSuccess={track.onShareLinkSuccess}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={paymentlinks.length}
              onClick={(params, type) => {
                const page = params.skip % params.count;
                track.paginate(type, page);
                this.paginate(params);
              }}
            />
            <EasterEgg extraClass="ftx-payment-links" page="Payment Links" />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

export default withRouter(withI18Service(PaymentLinksContainer));
