import React, { Component, Fragment, lazy, Suspense } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import QueryString from 'query-string';

import Header from 'common/ui/Header';
import Amount from 'common/ui/Amount';
import Sticky from 'common/ui/Sticky';
import DateRangePicker from 'common/ui/DateRangePicker';
import Popover, { PopoverBody } from 'common/ui/Popover';

import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Traffic from 'merchant/containers/Home/Traffic';
import Button from 'common/new-ui/Button';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import { openModal } from 'merchant_common/reducers/modals';
import Announcement from 'merchant/components/Announcements/Instant';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import OnboardingCard from 'v2/merchant/onboarding/mobile/Screens/Home';
import {
  trackPresetChange,
  trackSettlementsClick,
  trackSettleNow,
  EVENT_CATEGORY_DASHBOARD_HOME,
} from './ga';
import ShowWhen from 'merchant/components/ShowWhen';
import { fetchInstantSettlements } from 'merchant/reducers/collection';
import SettleNowLottie from 'merchant/helpers/lottieConfigs/SettleNow.json';
import SettleNowLottieHover from 'merchant/helpers/lottieConfigs/SettleNowHover.json';
import settleNowIcon from '../../../../icons/merchant/settle-now-thunder-dark.svg';
import LocalStorageService from 'common/utils/localStorage';
import { trackAnimatedSettleBtnImpressions } from 'merchant/views/Settlements/Settlements/ga';

const CustomLottie = lazy(() =>
  import(/* webpackChunkName: "CustomLottie" */ 'common/new-ui/Lottie'),
);
@connect(
  (state) => ({
    windowWidth: state.app.windowWidth,
    user: state.session.user,
    config: state.config,
  }),
  { openModal, fetchInstantSettlements },
)
class AnalyticsMobile extends Component {
  state = {
    settlementExists: true,
    hoverOnSettleButton: false,
  };

  constructor(props) {
    super(props);
    this.showOndemandSettlementForm = this.showOndemandSettlementForm.bind(this);
  }

  componentDidMount() {
    this.checkIfFirstEverSettlement();
    this.getSettlementDetails();
  }

  checkIfFirstEverSettlement = (callbackSettlementStatus) => {
    if (callbackSettlementStatus === 'settlementDone') {
      this.setState({ settlementExists: true });
      LocalStorageService.setItem('settlementExists', true);
    } else {
      const settlementExists = JSON.parse(LocalStorageService.getItem('settlementExists'));
      if (settlementExists) this.setState({ settlementExists });
      else this.getSettlementDetails();
    }
  };

  getSettlementDetails = () => {
    const { fetchInstantSettlements } = this.props;

    fetchInstantSettlements({ count: 1 })
      .then(({ data: { items = [] } = {} } = {}) => {
        this.setState({ settlementExists: items.length > 0 });
        LocalStorageService.setItem('settlementExists', items.length > 0);
      })
      .catch(() => {
        this.setState({ settlementExists: true });
      });
  };

  handleMouseActivityOverSettleBtn = (type) => {
    this.setState({ hoverOnSettleButton: type === 'mouseEnter' });
  };

  showOndemandSettlementForm() {
    const { current_balance, ondemand_restrictions, openModal, user } = this.props;
    trackSettleNow();
    const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');
    const balance = current_balance.data.balance;
    const settlableAmount = ondemand_restrictions && ondemand_restrictions.data.settlable_amount;
    openModal({
      component: (
        <OndemandModal
          animatedSettlemnetBtn={!this.state.settlementExists && esOndemandSettlementEnabled}
          currentBalance={balance}
          settlableAmount={settlableAmount}
          eventCategory={EVENT_CATEGORY_DASHBOARD_HOME}
          fromWhere="Home"
          checkIfFirstEverSettlement={this.checkIfFirstEverSettlement}
        />
      ),
      size: 'small',
      disableClose: true,
    });
  }

  defaultSettlementBtn = (checkIfSettlementDisabled) => {
    return (
      <Button.Secondary
        class="settle-btn settle-now--mobile settle-now--button"
        onClick={this.showOndemandSettlementForm}
        disabled={checkIfSettlementDisabled}
      >
        <img src={settleNowIcon} alt="settle-now-thunder" className="settlement-icon-thunder" />
        Settle Now
      </Button.Secondary>
    );
  };
  render() {
    const { settlementExists, hoverOnSettleButton } = this.state;
    const {
      config,
      current_balance,
      ondemand_restrictions,
      onExtraContentMount,
      isAdmin,
      onFetchPayments,
      scrollAmountToStickHeader,
      dateRangePresets,
      onDatesChange,
      defaultPreset,
      startDate,
      endDate,
      oldestTransactionDate,
      mode,
      user,
      showGroupingByPtfm,
      tabsMeta,
      analyticsFetch,
      onFilterChange,
      expandOnboardingBanner,
      showOnboardingBanner,
      showInstantActivation,
      payments,
      onHideOnboardingBanner,
      onFirstStepClose,
      showOnboardingBannerFirstStep,
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
      windowWidth,
      settleNowRestrictionMsg,
    } = this.props;

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;
    const query = QueryString.parse(window.location.search);

    const attemptsLeft = ondemand_restrictions && ondemand_restrictions.data.attempts_left;
    const isOndemandRestrictionsLoading = ondemand_restrictions && ondemand_restrictions.loading;
    const settlableAmount = ondemand_restrictions && ondemand_restrictions.data.settlable_amount;
    const isSettleNowRestricted =
      ondemand_restrictions && (!attemptsLeft || !settlableAmount || isOndemandRestrictionsLoading);
    const checkIfSettlementDisabled =
      isSettleNowRestricted || current_balance.loading || current_balance.data.balance < 100;
    const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');

    return (
      <div className="home-analytics-mobile">
        <div
          ref={(node) => onExtraContentMount(node)}
          className={`extra-content${showOnboardingBanner ? ' has-ob-banner' : ''}${
            !showOnboardingBanner && hasSecondaryBanner ? ' has-secondary-banner' : ''
          }`}
        >
          {showInstantActivation && !user.isOnboardingV2Enabled ? (
            <Announcement mode={mode} user={user} payments={payments} />
          ) : null}
          {!user.isOnboardingV2Enabled ? (
            <div className={`v2-onboarding-card${expandOnboardingBanner ? ' expand' : ''}`}>
              {showOnboardingBanner && (
                <NewUserOnboardingCard
                  payments={payments}
                  onClose={onHideOnboardingBanner}
                  onFirstStepClose={onFirstStepClose}
                  isFirstStep={showOnboardingBannerFirstStep}
                  showInstantActivation={showInstantActivation}
                />
              )}
            </div>
          ) : null}

          {user.isOnboardingV2Enabled ? <OnboardingCard /> : null}
          {hasSecondaryBanner && (
            <div className="secondary-announcement-banner">
              <PersonaliseBanner track={trackPersonaliseBanner} />
            </div>
          )}
          <Header className="clearfix" title="" showMode={false}>
            <div className={`pull-left ${this.props.user.isOndemandSettlementEnabled && 'm-t'}`}>
              Balance:{' '}
              <b>
                {!current_balance.loading && typeof current_balance.data.balance === 'number' && (
                  <Amount value={current_balance.data.balance} currency={'INR'} />
                )}
              </b>
            </div>
            <div className="pull-right">
              {this.props.user.isOndemandSettlementEnabled &&
              this.props.user.isAllowedView('early_settlement') ? (
                <div>
                  {!settlementExists && esOndemandSettlementEnabled ? (
                    <div
                      className=".settle-btn .settle-now--mobile"
                      onMouseEnter={() => this.handleMouseActivityOverSettleBtn('mouseEnter')}
                      onMouseLeave={() => this.handleMouseActivityOverSettleBtn('mouseLeave')}
                    >
                      <Suspense fallback={this.defaultSettlementBtn(checkIfSettlementDisabled)}>
                        <CustomLottie
                          onClick={this.showOndemandSettlementForm}
                          animationData={
                            hoverOnSettleButton ? SettleNowLottieHover : SettleNowLottie
                          }
                          autoplay={hoverOnSettleButton ? false : true}
                          loop={hoverOnSettleButton ? false : true}
                          width="138px"
                          isStopped={hoverOnSettleButton ? !hoverOnSettleButton : false}
                          disabled={checkIfSettlementDisabled}
                          trackInitialRenderImpression={trackAnimatedSettleBtnImpressions}
                          fromWhere="Home"
                          merchantId={user.current}
                        />
                      </Suspense>
                    </div>
                  ) : (
                    this.defaultSettlementBtn(checkIfSettlementDisabled)
                  )}

                  {settleNowRestrictionMsg && (
                    <Popover
                      align="top"
                      parentQuerySelector={`.settle-btn .settle-now--mobile`}
                      theme="dark"
                    >
                      <PopoverBody>{settleNowRestrictionMsg}</PopoverBody>
                    </Popover>
                  )}
                </div>
              ) : (
                <Link className="pull-right" to="/settlements">
                  <span className="text-no-wrap" onClick={trackSettlementsClick}>
                    View Settlements <i className="i i-chevron-right" />
                  </span>
                </Link>
              )}
            </div>
          </Header>
          {!isAdmin && (
            <div className="content">
              <p className="section-title">{recentActivityTitle}</p>
              <RecentActivity
                sectionTitle={recentActivityTitle}
                onFetchPayments={onFetchPayments}
                isTabletResolution={true}
                user={this.props.user}
                currentBalance={current_balance}
                onSelect={this.showOndemandSettlementForm}
              />
            </div>
          )}
        </div>
        <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}>
          <Header className="clearfix" title="" showMode={false}>
            <div id="analytics-daterange-picker" className="date-range-container">
              <DateRangePicker
                presets={dateRangePresets}
                onDatesChange={onDatesChange}
                defaultPreset={defaultPreset}
                onSelectPreset={trackPresetChange}
                numberOfMonths={1}
                horizontalMargin={
                  // adjusting the right position of datepicker so that
                  // it does not overflow, for screen resolution <= 424
                  // presets are hidden , so not adjusting the DRP
                  windowWidth < 530
                    ? windowWidth > 424
                      ? 530 - windowWidth
                      : windowWidth > 360
                      ? 40
                      : 57
                    : 0
                }
              />
            </div>
          </Header>
        </Sticky>
        <div className="dashboard">
          <KeyMetrics
            startDate={startDate}
            endDate={endDate}
            oldestTransactionDate={oldestTransactionDate}
            mode={mode}
            showGroupingByPtfm={showGroupingByPtfm}
            sectionTitle={''}
            tabsMeta={tabsMeta}
            isAdmin={isAdmin}
            analyticsFetch={analyticsFetch}
            onFilterChange={onFilterChange}
            isMobile={true}
          />
          <p className="section-title">{paymentInsightsTitle}</p>
          <PaymentMethods
            startDate={startDate}
            endDate={endDate}
            mode={mode}
            analyticsFetch={analyticsFetch}
            sectionTitle={paymentInsightsTitle}
            isMobile={true}
          />
          {showGroupingByPtfm && (
            <React.Fragment>
              <p className="section-title">{trafficSectionTitle}</p>
              <Traffic
                startDate={startDate}
                endDate={endDate}
                mode={mode}
                analyticsFetch={analyticsFetch}
                sectionTitle={''}
                isMobile={true}
              />
            </React.Fragment>
          )}
        </div>
      </div>
    );
  }
}

export default AnalyticsMobile;
