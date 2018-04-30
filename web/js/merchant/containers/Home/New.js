import React, { Component } from 'react';
import Header from 'rzp/ui/Header';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import * as HomeActions from 'merchant/modules/home';
import moment from 'moment';
import { Redirect } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Banner from 'rzp/ui/Banner';
import Sticky from 'rzp/ui/Sticky';
import Group, { GroupItem } from 'rzp/ui/Group';
import LocalStorageService from 'rzp/utils/localStorage';
import { showNotification } from 'rzp/modules/notifications';
import DateRangePicker, { customRangeText } from 'rzp/ui/DateRangePicker';
import Popover, { PopoverTitle, PopoverBody } from 'rzp/ui/Popover';
import {
  oldestTransactionQuery,
  getDefaultPaymentFilter,
  platformGroupingVals,
  groupByPlatform,
  OTHERS,
} from 'rzp/utils/pokedex';

import { fetch } from 'merchant/modules/pokedex';
import { fetchPayments } from 'rzp/modules/collection';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import {
  API_ERROR,
  API_INVALID_RESP,
  isMobileDevice,
} from 'merchant/components/Home/data';
import GenericPanel, { PanelBody } from 'merchant/components/Home/GenericPanel';
import { showOrHideTour } from 'merchant/modules/session';

import {
  trackError,
  trackDatesChange,
  trackPresetChange,
  trackSettlementsClick,
  trackPlatformAnalyticsHidden,
  trackForceOldDashboard,
  trackViewTour,
} from './ga';

const dateRangePresets = [
    ['Past 7 Days', -7, 'days'],
    ['Past 30 Days', -30, 'days'],
    ['Past 90 Days', -90, 'days'],
    ['All Time', -10, 'years'],
  ],
  defaultPreset = 1;

const getPreviousDates = ({ startDate, endDate }) => {
  const diff = endDate.diff(startDate);

  return {
    startDate: startDate.clone().subtract(diff, 'ms'),
    endDate: endDate
      .clone()
      .subtract(1, 'day')
      .subtract(diff, 'ms')
      .endOf('day'),
  };
};

const bodyClass = ' analytics-v2-active';

// used to show titles for sections and also GA
const keymetricsSectionTitle = 'Transactions Overview',
  paymentInsightsTitle = 'Payment Insights',
  trafficSectionTitle = 'Traffic split on platforms',
  recentActivityTitle = 'Recent Activity';

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
      current_balance: state.home.current_balance,
    };
  },
  {
    ...HomeActions,
    showNotification,
    showOrHideTour,
    fetchPayments,
  }
)
class HomeContainer extends Component {
  constructor(props) {
    super(props);

    // recording new analytics interactions in hotjar
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'new_analytics');
      window.hj('tagRecording', ['new_analytics']);
    }

    let endDate = moment().endOf('day'),
      startDate = endDate.clone().startOf('day');

    startDate.add(...dateRangePresets[defaultPreset].slice(1));

    const { user, mode, isAdmin } = props,
      // onboarding card is shown if this is present in localstorage
      onboardingCardToken = 'show_onboarding_card',
      // onboarding card first step is shown if this is present in localstorage
      firstStepToken = 'onboarding_first_step';

    // tokens particular for the current merchant
    this.onboardingBannerToken = `${onboardingCardToken}--${user.current}`;
    this.firstStepToken = `${firstStepToken}--${user.current}`;

    /*
     * Earlier , the tokens apply at browser level, if old tokens are present
     * converting them specific to the merchants the current user can switch to
     */
    if (LocalStorageService.getItem(onboardingCardToken)) {
      Object.keys(user.merchants).forEach(key => {
        LocalStorageService.setItem(`${onboardingCardToken}--${key}`, 'true');
      });

      LocalStorageService.removeItem(onboardingCardToken);
    }

    if (LocalStorageService.getItem(firstStepToken)) {
      Object.keys(user.merchants).forEach(key => {
        LocalStorageService.setItem(`${firstStepToken}--${key}`, 'true');
      });

      LocalStorageService.removeItem(firstStepToken);
    }

    const hasAccessToOnboardingBanner = (this.hasAccessToOnboardingBanner =
      ['manager', 'owner', 'admin'].indexOf(user.role) >= 0);

    const showOnboardingBanner =
        hasAccessToOnboardingBanner &&
        LocalStorageService.getItem(this.onboardingBannerToken),
      showOnboardingBannerFirstStep = LocalStorageService.getItem(
        this.firstStepToken
      );

    this.state = {
      startDate,
      endDate,
      oldestTransactionDate: {
        value: null,
        loading: false,
        error: '',
        ...getPreviousDates({ startDate, endDate }),
      },
      dateRangePresets,
      showGroupingByPtfm: false,
      scrollAmountToStickHeader: 0,
      hasNewAnalyticsTour:
        !isAdmin &&
        mode === 'live' &&
        !LocalStorageService.getItem('hide_new_analytics_banner'),
      dismissNewAnalyticsBanner: false, // used for transition
      expandOnboardingBanner: showOnboardingBanner, // used for transition
      showOnboardingBanner,
      showOnboardingBannerFirstStep,
      // payments is used to change content in the integration step
      payments: {
        loading: true,
        items: [],
      },
    };

    /*
     * If token not present to show the banner,
     * Need to show the banner until the user integrates in live mode
     * which we can check by checking his live transactions
     *
     * If the user is in live mode, we make fetchAll payments in 
     * RecentActivity component, which will be done using `onFetchPayments`
     * below
     */
    if (hasAccessToOnboardingBanner && !showOnboardingBanner) {
      if (!user.isActivated) {
        this.state = {
          ...this.state,
          showOnboardingBanner: true,
          showOnboardingBannerFirstStep: true,
          expandOnboardingBanner: true,
        };

        LocalStorageService.setItem(this.onboardingBannerToken, 'true');
        LocalStorageService.setItem(this.firstStepToken, 'true');
      } else if (mode !== 'live') {
        this.props.fetchPayments({ mode: 'live' }).then(data => {
          data = data.data;

          if (data && data.items && data.items.length === 0) {
            this.setShowOnboardingBanner();
          }
        });
      }
    }

    this.oldestTxnReqId = 0;
    this.onDatesChange = this.onDatesChange.bind(this);
    this.onShowTour = this.onShowTour.bind(this);
    this.onFetchPayments = this.onFetchPayments.bind(this);
    this.setScrollAmountToStickHeader = this.setScrollAmountToStickHeader.bind(
      this
    );
    this.onHideOnboardingBanner = this.onHideOnboardingBanner.bind(this);
    this.onHideNewAnalyticsBanner = this.onHideNewAnalyticsBanner.bind(this);
    this.onFirstStepClose = this.onFirstStepClose.bind(this);
  }

  fetchTxnsGroupedByPlatform() {
    // need to figureout whether we should show group by platform
    // or not

    const { startDate, endDate } = this.state,
      { isAdmin, analyticsFetch } = this.props;

    const query = {
      filters: {
        default: [getDefaultPaymentFilter(startDate.unix(), endDate.unix())],
      },
      aggregations: {
        records: {
          agg_type: 'count',
          details: {
            index: 'payments',
            group_by: platformGroupingVals,
          },
        },
      },
    };

    return (analyticsFetch || fetch)(query, this.props.mode)
      .then(data => {
        if (!data.success) {
          return API_ERROR;
        }

        if (!data.data || !data.data.records) {
          return API_INVALID_RESP;
        }

        data = groupByPlatform(data.data.records.result);

        return data;
      })
      .catch(err => {
        console.error(err);

        return API_ERROR;
      })
      .then(data => {
        if (data.error) {
          trackError(`While Fetching Txns Grouped by Ptfm`);

          return this.props.showNotification({
            type: 'error',
            message: data.error,
            hidePrevious: true,
          });
        }

        if (!isAdmin) {
          const platforms = Object.keys(data);

          // if we do not get platforms for given daterange
          // do not show grouping
          if (platforms.length === 0) {
            return;
          }

          let grandTotal = 0;

          const totalByPlatform = platforms.reduce((group, platform) => {
            group[platform] = data[platform].reduce((sum, entry) => {
              return sum + entry.value;
            }, 0);

            grandTotal += group[platform];

            return group;
          }, {});

          // if the txn count of platforms for given daterange
          // do not show grouping
          if (grandTotal === 0) {
            return;
          }

          const ratio = (totalByPlatform[OTHERS] || 0) / grandTotal;

          // if `Others` platform count is greater than 30%
          // do not show grouping
          if (ratio > 0.3) {
            trackPlatformAnalyticsHidden(ratio * 100);
            return;
          }
        }

        // this will show group by platform dropdowns and also
        // traffic graph
        this.setState({
          showGroupingByPtfm: true,
        });
      });
  }

  fetchOldestTransactionDate() {
    let { oldestTransactionDate, dateRangePresets } = this.state;

    const { onFirstTxnDate, analyticsFetch } = this.props;

    var oldestTxnReqId = ++this.oldestTxnReqId;

    oldestTransactionDate = { ...oldestTransactionDate };

    oldestTransactionDate.error = '';
    oldestTransactionDate.loading = true;

    this.setState({
      oldestTransactionDate: { ...oldestTransactionDate },
    });

    return (analyticsFetch || fetch)(oldestTransactionQuery, this.props.mode)
      .then(data => {
        if (oldestTxnReqId !== this.oldestTxnReqId) {
          return null;
        }

        if (!data.success) {
          return API_ERROR;
        }

        if (!data.data || !data.data.records) {
          return API_INVALID_RESP;
        }

        const records = data.data.records.result[0],
          value = records && records.created_at;

        return { value };
      })
      .catch(err => {
        console.error(err);

        return API_ERROR;
      })
      .then(data => {
        oldestTransactionDate.loading = false;

        if (!data || data.error) {
          if (data.error) {
            oldestTransactionDate.error = data.error;

            trackError(`While Fetching Oldest txn date`);

            this.props.showNotification({
              type: 'error',
              message: data.error,
              hidePrevious: true,
            });
          }

          this.setState({
            oldestTransactionDate,
          });

          return onFirstTxnDate && onFirstTxnDate();
        }

        const presetsLastIndex = dateRangePresets.length - 1,
          presetsLastItem = dateRangePresets[presetsLastIndex];

        // updates All Time present in daterange picker
        dateRangePresets = [...dateRangePresets];

        dateRangePresets.splice(presetsLastIndex, 1, [
          presetsLastItem[0],
          -(moment().unix() - data.value),
          'seconds',
        ]);

        this.setState({
          oldestTransactionDate: {
            ...oldestTransactionDate,
            value: data.value,
          },
          dateRangePresets,
        });

        return onFirstTxnDate && onFirstTxnDate(data.value);
      });
  }

  onDatesChange(startDate, endDate, selectedPreset) {
    const { oldestTransactionDate } = this.state;

    this.setState({
      startDate,
      endDate,
      oldestTransactionDate: {
        ...oldestTransactionDate,
        ...getPreviousDates({ startDate, endDate }),
      },
    });

    if (selectedPreset.name === customRangeText) {
      trackDatesChange(startDate, endDate);
    }
  }

  componentWillMount() {
    // to style react-power-selct specific to this tab
    document.body.className += bodyClass;

    this.props.fetchCurrentBalance();
    this.fetchOldestTransactionDate();
    this.fetchTxnsGroupedByPlatform();
  }

  componentWillUnmount() {
    document.body.className = document.body.className.replace(bodyClass, '');
  }

  setScrollAmountToStickHeader() {
    const scrollAmountToStickHeader = this.extraContent
      ? this.extraContent.clientHeight
      : 0;

    this.setState({ scrollAmountToStickHeader });
  }

  componentDidMount() {
    this.setScrollAmountToStickHeader();
  }

  onFirstStepClose() {
    this.setState(
      {
        showOnboardingBannerFirstStep: false,
      },
      () => {
        // when first step is closed, the banner height gets changes,
        // adjusting the scroll amount when the datepicker bar should stick
        // on top of the page
        this.setScrollAmountToStickHeader();
      }
    );

    LocalStorageService.removeItem(this.firstStepToken);
  }

  onHideOnboardingBanner() {
    this.setState(
      {
        expandOnboardingBanner: false,
      },
      () => {
        this.setState({
          showOnboardingBanner: false,
        });

        this.setScrollAmountToStickHeader();
      }
    );

    LocalStorageService.removeItem(this.onboardingBannerToken);
  }

  setShowOnboardingBanner() {
    this.setState(
      {
        showOnboardingBanner: true,
        showOnboardingBannerFirstStep: true,
      },
      () => {
        this.setState(
          {
            expandOnboardingBanner: true,
          },
          () => {
            this.setScrollAmountToStickHeader();
          }
        );
      }
    );

    LocalStorageService.setItem(this.onboardingBannerToken, 'true');
    LocalStorageService.setItem(this.firstStepToken, 'true');
  }

  onFetchPayments(data) {
    const { user, mode } = this.props;

    const items = (data && data.items) || [];

    const { showOnboardingBanner } = this.state;

    this.setState({
      payments: {
        loading: false,
        items,
      },
    });

    /*
     * When fetched payments in live mode, using recent activity component
     * we use it to show the banner , if there are no trasaction
     */

    if (user.isActivated && mode === 'live') {
      // show hotjar if number of payments is greater than 50
      if (items.length > 50) {
        document.body.className += ' show-hotjar-poll';
      }

      if (
        this.hasAccessToOnboardingBanner &&
        !this.state.showOnboardingBanner &&
        items.length === 0
      ) {
        this.setShowOnboardingBanner();
      }
    }
  }

  onShowTour() {
    this.onHideNewAnalyticsBanner(() => {
      this.props.showOrHideTour(true);
    });

    trackViewTour();
  }

  onHideNewAnalyticsBanner(cb) {
    LocalStorageService.setItem('hide_new_analytics_banner', true);

    this.setState(
      {
        dismissNewAnalyticsBanner: true,
      },
      () => {
        window.setTimeout(() => {
          this.setState(
            {
              dismissNewAnalyticsBanner: false,
              hasNewAnalyticsTour: false,
            },
            () => {
              this.setScrollAmountToStickHeader();
              typeof cb === 'function' && cb();
            }
          );
        }, 500); // let the trasition to hide banner complete
      }
    );
  }

  render() {
    let {
      mode,
      user,
      current_balance,
      tabsMeta,
      isAdmin,
      analyticsFetch,
      onFilterChange,
    } = this.props;

    const {
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
      showGroupingByPtfm,
      hasNewAnalyticsTour,
      scrollAmountToStickHeader,
      dismissNewAnalyticsBanner,
      showOnboardingBanner,
      expandOnboardingBanner,
    } = this.state;

    return (
      <div class="react-root dashboard-home">
        <div ref={node => (this.extraContent = node)} className="extra-content">
          {!isAdmin && (
            <div>
              {hasNewAnalyticsTour && (
                <div
                  className={`v2-tour-banner${
                    dismissNewAnalyticsBanner ? ' dismiss' : ''
                  }`}
                >
                  <div className="banner-icon">
                    <i className="i i-loudspeaker" />
                  </div>
                  <div className="banner-content">
                    <Banner cta="View Tour" ctaOnClick={this.onShowTour}>
                      <span>
                        We heard you! We have updated the dashboard home design
                        for an improved experience.
                      </span>
                    </Banner>
                  </div>
                  <div className="banner-close">
                    <a
                      className="banner-close-icon"
                      onClick={this.onHideNewAnalyticsBanner}
                    >
                      <i className="i i-close" />
                    </a>
                  </div>
                </div>
              )}
            </div>
          )}
          <div
            className={`v2-onboarding-card${
              expandOnboardingBanner ? ' expand' : ''
            }`}
          >
            {showOnboardingBanner && (
              <NewUserOnboardingCard
                payments={this.state.payments}
                onClose={this.onHideOnboardingBanner}
                onFirstStepClose={this.onFirstStepClose}
                isFirstStep={this.state.showOnboardingBannerFirstStep}
              />
            )}
          </div>
        </div>
        <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}>
          <Header className="clearfix" title="" showMode={false}>
            <div
              id="analytics-daterange-picker"
              className="pull-left date-range-container"
            >
              <DateRangePicker
                presets={dateRangePresets}
                onDatesChange={this.onDatesChange}
                defaultPreset={defaultPreset}
                onSelectPreset={trackPresetChange}
              />
            </div>
            <div className="pull-right">
              <Group>
                <GroupItem>
                  <span className="balance-amount">
                    Current Balance:{' '}
                    {!current_balance.loading && (
                      <Amount value={current_balance.data.balance} />
                    )}
                  </span>
                </GroupItem>
                <GroupItem>
                  <Link className="pull-right" to="/settlements">
                    <span
                      className="text-no-wrap"
                      onClick={trackSettlementsClick}
                    >
                      View Settlements
                    </span>
                  </Link>
                </GroupItem>
              </Group>
            </div>
          </Header>
        </Sticky>

        <div className="dashboard">
          <div className="row">
            <div className="col-md-12">
              <KeyMetrics
                startDate={startDate}
                endDate={endDate}
                oldestTransactionDate={oldestTransactionDate}
                mode={mode}
                showGroupingByPtfm={showGroupingByPtfm}
                sectionTitle={keymetricsSectionTitle}
                tabsMeta={tabsMeta}
                isAdmin={isAdmin}
                analyticsFetch={analyticsFetch}
                onFilterChange={onFilterChange}
              />
            </div>
          </div>

          <div className="row">
            <div className="col-md-12">
              <div className="section-title payment-insights-title">
                {paymentInsightsTitle}&nbsp;
                <small>
                  <i class="i i-help" />
                  <Popover align="top">
                    <PopoverBody>
                      <p>
                        This graph helps you gain insights into your overall
                        payments by seeing how different payment methods stack
                        up against each other in your revenue pool.
                      </p>
                      <div>
                        <span className="popover-highlight">Click tiles</span>{' '}
                        to drill-down into the hierarchy.
                      </div>
                      <div>
                        <span className="popover-highlight">Hover</span> to view
                        information for smaller tiles.
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
              </div>
            </div>
            <div className="col-md-12">
              <PaymentMethods
                startDate={startDate}
                endDate={endDate}
                mode={mode}
                analyticsFetch={analyticsFetch}
                sectionTitle={paymentInsightsTitle}
              />
            </div>
          </div>

          <div className="row">
            <div
              className={`col-md-12 traffic-activity-row clearfix${
                showGroupingByPtfm ? '' : ' traffic-hidden'
              }`}
            >
              {showGroupingByPtfm && (
                <div className="traffic-container">
                  <p className="content-title section-title">
                    {trafficSectionTitle}
                  </p>
                  <div className="content">
                    <Traffic
                      startDate={startDate}
                      endDate={endDate}
                      mode={mode}
                      analyticsFetch={analyticsFetch}
                      sectionTitle={trafficSectionTitle}
                    />
                  </div>
                </div>
              )}
              {!isAdmin && (
                <div className="activity-container">
                  <p className="content-title section-title">
                    {recentActivityTitle}
                  </p>
                  <div className="content">
                    <RecentActivity
                      sectionTitle={recentActivityTitle}
                      onFetchPayments={this.onFetchPayments}
                    />
                  </div>
                </div>
              )}
            </div>
          </div>

          <div className="row home-credits-section">
            <div className="col-md-12">
              <GenericPanel>
                <PanelBody>
                  <div className="text-center">
                    <small>
                      <i class="icon icon-info-circle" /> Please share your
                      feedback/suggestions by clicking the Feedback button on
                      the right edge of your screen. You could also write to us
                      at{' '}
                      <a target="_blank" href="mailto:support@razorpay.com">
                        support@razorpay.com
                      </a>.
                    </small>
                  </div>
                </PanelBody>
              </GenericPanel>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default props =>
  isMobileDevice ? (
    (trackForceOldDashboard(), <Redirect to="/dashboard" />)
  ) : (
    <HomeContainer {...props} />
  );
