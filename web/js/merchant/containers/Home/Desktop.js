import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { Link } from 'react-router-dom';

import Header from 'common/ui/Header';
import Amount from 'common/ui/Amount';
import Sticky from 'common/ui/Sticky';
import Group, { GroupItem } from 'common/ui/Group';
import DateRangePicker from 'common/ui/DateRangePicker';
import Popover, { PopoverBody } from 'common/ui/Popover';
import ShowWhen from 'merchant/components/ShowWhen';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import KeyMetrics from 'merchant/containers/Home/KeyMetrics';
import PaymentMethods from 'merchant/containers/Home/PaymentMethods';
import Traffic from 'merchant/containers/Home/Traffic';
import RecentActivity from 'merchant/containers/Home/RecentActivity';
import Announcement from 'merchant/components/Announcements/Instant';
import NPSAnnouncement from 'merchant/components/Announcements/NPSAnnouncement';
import CapitalAnnouncement from 'merchant/components/Announcements/Capital';
import CovidCampaignAnnouncement from 'merchant/components/Announcements/CovidCampaign';
import PersonaliseBanner from 'merchant/components/Announcements/PersonaliseAccount';
import InternationalRequestStatusAnnouncement from 'merchant/components/Announcements/InternationalRequestStatus';
import Button from 'common/new-ui/Button';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import { openModal } from 'merchant_common/reducers/modals';
import { fetchInternationalProductsStatus } from 'merchant/reducers/config';
import { trackPersonaliseBanner } from 'merchant/containers/Home/OnboardingCard/Instant/ga';

import CreditPullModal from 'merchant/containers/CreditPullModal';
import {
  trackPresetChange,
  trackSettlementsClick,
  trackSettleNow,
  EVENT_CATEGORY_DASHBOARD_HOME,
} from './ga';
import OnHoldBanner from 'common/ui/OnHoldBanner';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import { handleNegativeBalanceLimit } from 'common/utils/rzp-utils';
import Time from 'common/ui/Time';
import NCModal from 'merchant/components/Activation/NCModal';
import { merchantFetch } from 'merchant/utils/ajax';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    config: state.config,
    tls_version: state.profile.tls_version,
    internationalProductsStatus: state.config.internationalProductsStatus,
  }),
  {
    openModal,
    fetchInternationalProductsStatus,
  },
)
class AnalyticsDesktop extends Component {
  state = {
    showNcPopup: true,
    whatsappNotificationStatus: 'off',
  };
  constructor(props) {
    super(props);

    this.showOndemandSettlementForm = this.showOndemandSettlementForm.bind(this);
  }

  popupCredit = () => {
    if (this.props.location.hash === '#creditscore') {
      this.resetHash();
      this.props.openModal({
        component: <CreditPullModal fromWhere="Announcements" />,
        size: 'regular',
      });
    }
  };

  componentDidUpdate() {
    this.popupCredit();
  }

  componentDidMount() {
    analyticsService.track({
      objectName: 'home page',
      actionName: 'displayed',
      screen: 'home page',
    });
    this.props.fetchInternationalProductsStatus();
    merchantFetch({
      url: `users/whatsapp/opt_in_status`,
      method: 'get',
      data: { source: 'pg.settings.config' },
    })
      .then((response) => {
        if (response && response.data && response.data.consent_status)
          this.setState({ whatsappNotificationStatus: 'on' });
        else {
          this.setState({ whatsappNotificationStatus: 'off' });
        }
      })
      .catch((_) => {
        this.setState({ whatsappNotificationStatus: 'off' });
      });
  }

  resetHash = () => {
    this.props.history.push({
      pathname: this.props.history.location.pathname,
      hash: '',
    });
  };

  showOndemandSettlementForm() {
    trackSettleNow();
    const balance = this.props.current_balance.data.balance;
    this.props.openModal({
      component: (
        <OndemandModal
          currentBalance={balance}
          eventCategory={EVENT_CATEGORY_DASHBOARD_HOME}
          fromWhere="Home"
        />
      ),
      size: 'small',
      disableClose: true,
    });
  }

  isCaptureSettingsDefault = (items) => {
    if (!items) return false;
    else {
      if (items[0]) {
        // check for created_at & updated_at in config
        const config = items[0];
        const isSame = moment(config.created_at).isSame(config.updated_at);
        // If both created_at & updated_at are same, only then show banner
        return isSame;
      } else return false;
    }
  };

  onNcModalClose = () => {
    this.setState({
      showNcPopup: false,
    });
  };

  isWhatsappNotificationEnabled = (user) => {
    return (
      user.isWhatsappNotificationEnabled() &&
      user.contact_mobile &&
      user.activation_status === 'activated' &&
      user.role === 'owner'
    );
  };

  renderWhatsappNotification = () => {
    if (this.state.whatsappNotificationStatus === 'off')
      return (
        <AnnouncementBanner title="WhatsApp Notifications" theme="success" canBeClosed={true}>
          Receive account-related notifications on WhatsApp. &nbsp;
          <Link
            onClick={() => {
              analyticsService.track({
                objectName: 'banner',
                actionName: 'clicked',
                screen: 'home page',
                properties: {
                  hyperlinkClicked: 'Enable Notifications',
                  title: 'WhatsApp Notifications',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
            }}
            to="/config#whatsapp_enable_on"
          >
            Enable Notifications
          </Link>
        </AnnouncementBanner>
      );
    else {
      return (
        <AnnouncementBanner title="WhatsApp Notifications" theme="success" canBeClosed={true}>
          You will now receive account-related notifications on WhatsApp. &nbsp;
          <Link
            onClick={() => {
              analyticsService.track({
                objectName: 'banner',
                actionName: 'clicked',
                screen: 'home page',
                properties: {
                  hyperlinkClicked: 'Manage settings here',
                  title: 'WhatsApp Notifications',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
            }}
            to="/config#whatsapp_enable_control"
          >
            Manage settings here
          </Link>
        </AnnouncementBanner>
      );
    }
  };

  showGSTOptOutFlow = () => {
    if (this.props.user.features) {
      const show = this.props.user.features.filter((f) => f.feature === `suggested_address_opt_in`);
      if (show.length > 0) return true;
      else;
      return false;
    } else {
      return false;
    }
  };

  render() {
    const {
      mode,
      user,
      config,
      current_balance,
      tabsMeta,
      isAdmin,
      analyticsFetch,
      onFilterChange,
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
      showGroupingByPtfm,
      showOnboardingBannerFirstStep,
      expandOnboardingBanner,
      payments,
      showOnboardingBanner,
      showInstantActivation,
      onHideOnboardingBanner,
      onFirstStepClose,
      onDatesChange,
      onFetchPayments,
      onExtraContentMount,
      settlement_amount,
      defaultPreset,
      keymetricsSectionTitle,
      paymentInsightsTitle,
      recentActivityTitle,
      trafficSectionTitle,
      merchantBalanceConfigs,
      lateAuthConfig,
      hasMinTransactionSD,
      isValueFilled,
      roleToShowSupportDetailForm,
      openSupportDetailModal,
    } = this.props;

    const {
      data: { items },
    } = lateAuthConfig;

    const hasSecondaryBanner =
      showInstantActivation && config.config && !config.config.hasPersonalised;

    const nextSettlement = !settlement_amount.data.next_settlement_time;
    const { no_settlement } = settlement_amount.data;

    let balance = current_balance.data.balance;
    let negativeBalanceClassName = '';

    if (balance < 0) {
      balance = Math.abs(current_balance.data.balance);
      negativeBalanceClassName = 'negative-balance';
    }
    return (
      <div className="home-analytics-desktop">
        <div
          ref={(node) => onExtraContentMount(node)}
          className={`extra-content${showOnboardingBanner ? ' has-ob-banner' : ''}${
            !showOnboardingBanner && hasSecondaryBanner ? ' has-secondary-banner' : ''
          }`}
        >
          {/* nps banner */}
          {user.isAccepted && <NPSAnnouncement user={user} />}

          {/* onboarding banner */}
          {showInstantActivation && <Announcement mode={mode} user={user} payments={payments} />}

          {/* international onboarding banner */}
          {mode === 'live' &&
            user.instantActivation.isGraylistFlow &&
            user.internationalActivationFlow.isGraylistFlow && (
              <InternationalRequestStatusAnnouncement
                internationalProductsStatus={this.props.internationalProductsStatus}
              />
            )}

          {/* needs clarification modal */}
          {this.state.showNcPopup && user.needsClarification && (
            <NCModal onClose={this.onNcModalClose} />
          )}

          {this.isCaptureSettingsDefault(items) && user.instantActivation.isWhitelistFlow === true && (
            <AnnouncementBanner title="Capture Settings" theme="success" canBeClosed={true}>
              Currently all payments with order id are being captured by default, click{' '}
              <Link
                onClick={() => {
                  analyticsService.track({
                    objectName: 'banner',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      hyperlinkClicked: 'here',
                      title: 'Capture Settings',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
                to="/config"
                target="_blank"
              >
                here
              </Link>{' '}
              to configure your capture setting.
            </AnnouncementBanner>
          )}

          {this.showGSTOptOutFlow() === true && (
            <AnnouncementBanner title="GST Address Mismatch" theme="warning" canBeClosed={false}>
              The business address you provided to Razorpay does not match with your address details
              on your GST certificate. On Jan 25, 2021, we will update your address to the same as
              your GST details.{' '}
              <Link
                class="Button--secondary Button scheduled-btn-act btn-border"
                onClick={() => {}}
                to="/profile#gst"
                style={{ display: 'inline-block', marginTop: '4px' }}
              >
                Review address
              </Link>
            </AnnouncementBanner>
          )}

          {current_balance.data.balance < 0 && (
            <AnnouncementBanner title="Add Funds" theme="warning" canBeClosed={true}>
              Your balance went into negative value. Add funds to avoid the transaction failures.{' '}
              <Link
                onClick={() => {
                  analyticsService.track({
                    objectName: 'banner',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      hyperlinkClicked: 'Add Funds',
                      title: 'Add Funds',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
                to="/addfunds"
                target="_blank"
              >
                {' '}
                Add Funds
              </Link>
            </AnnouncementBanner>
          )}

          {handleNegativeBalanceLimit(merchantBalanceConfigs, current_balance.data.balance) && (
            <AnnouncementBanner title="On Hold!" theme="danger" canBeClosed={true}>
              Your current balance had reached the maximum negative limit. Transactions will start
              to fail now. Please add funds to avoid transaction failures.{' '}
              <Link
                onClick={() => {
                  analyticsService.track({
                    objectName: 'banner',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      hyperlinkClicked: 'Add Funds',
                      title: 'On Hold!',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
                to="/addfunds"
                target="_blank"
              >
                {' '}
                Add Funds
              </Link>
            </AnnouncementBanner>
          )}
          {hasMinTransactionSD && !isValueFilled && roleToShowSupportDetailForm && (
            <AnnouncementBanner title="Add Support Details" theme="primary" canBeClosed={true}>
              <span className="support-tagline">
                Let your customers know how to reach you for any queries.
              </span>
              <Link
                onClick={() => {
                  analyticsService.track({
                    objectName: 'banner',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      hyperlinkClicked: 'Add Details',
                      title: 'Add Support Details',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
                to="/profile"
              >
                <button
                  className="pull-right primary btn-support"
                  type="button"
                  onClick={() => openSupportDetailModal(false)}
                >
                  Add Details
                </button>
              </Link>
            </AnnouncementBanner>
          )}

          {this.isWhatsappNotificationEnabled(user) && this.renderWhatsappNotification()}
          {/* security upgrade browser banner */}
          {this.props.tls_version === '1.0' || this.props.tls_version === '1.1' ? (
            <AnnouncementBanner title="Upgrade your browser" theme="danger" canBeClosed={true}>
              Please upgrade your browser to continue accessing this site. We are disabling support
              for browsers which use TLS 1.0 and 1.1 for security reasons.
            </AnnouncementBanner>
          ) : (
            <Fragment>
              {/* capital banner*/}
              {user.isCapitalBannerEnabled && <CapitalAnnouncement userId={user.current} />}

              {user.isCovidFeatureEnabled && <CovidCampaignAnnouncement userId={user.current} />}

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
            </Fragment>
          )}
          {hasSecondaryBanner && (
            <div className="secondary-announcement-banner">
              <PersonaliseBanner track={trackPersonaliseBanner} />
            </div>
          )}
        </div>
        {/* <Sticky stickWhen={scrollAmountToStickHeader} stickAt={50}> */}
        <Header className="clearfix" title="" showMode={false}>
          <div id="analytics-daterange-picker" className="pull-left date-range-container">
            <DateRangePicker
              presets={dateRangePresets}
              onDatesChange={onDatesChange}
              defaultPreset={defaultPreset}
              onSelectPreset={trackPresetChange}
            />
          </div>
          <div
            className={`pull-right ${
              this.props.user.isOndemandSettlementEnabled ? 'ondemand-enabled' : ''
            }`}
          >
            <Group>
              {this.props.user.isOrgAllowedFunctionality('current_balance') && (
                <GroupItem>
                  <div style={{ textAlign: 'right' }}>
                    <span class="settlement-balance-amount">
                      <strong>Current Balance: </strong>
                      {!current_balance.loading && (
                        <Amount
                          value={balance}
                          currency="INR"
                          className={negativeBalanceClassName}
                        />
                      )}
                    </span>
                    <br />
                    {no_settlement && payments && payments.items.length > 0 && mode === 'live' ? (
                      <div class="text-right" style={{ width: '100%' }}>
                        {no_settlement.caption}
                        {no_settlement.reason && (
                          <div style={{ display: 'inline' }}>
                            <i class="i i-info-circle" />
                            <Popover theme="dark" align="left">
                              <PopoverBody>
                                <div>{no_settlement.reason}</div>
                              </PopoverBody>
                            </Popover>
                          </div>
                        )}
                      </div>
                    ) : null}
                    {!no_settlement && !nextSettlement ? (
                      <div class="text-right" style={{ width: '100%' }}>
                        <strong>
                          <Amount value={settlement_amount.data.settlement_amount} currency="INR" />
                        </strong>{' '}
                        will be settled on{' '}
                        <Time
                          value={settlement_amount.data.next_settlement_time}
                          format="DD MMM YYYY, hh:mm:ss a"
                        />{' '}
                        {settlement_amount.data.reason_for_delay && (
                          <div style={{ display: 'inline' }}>
                            <i class="i i-info-circle" />
                            <Popover theme="dark" align="left">
                              <PopoverBody>
                                <div>{settlement_amount.data.reason_for_delay}</div>
                              </PopoverBody>
                            </Popover>
                          </div>
                        )}
                        <span
                          class="btn-link"
                          style={{ marginLeft: '5px', fontWeight: 'bold' }}
                          onClick={() => {
                            this.props.openModal({
                              size: 'medium',
                              component: (
                                <SettlementDetail
                                  user={user}
                                  settlementAmount={settlement_amount.data}
                                />
                              ),
                            });
                            window.rzpAnalytics({
                              eventCategory: 'Settlement Revamp',
                              eventAction: 'Know more - Next Settlement',
                              eventLabel: `Home`,
                            });
                          }}
                        >
                          Know more
                        </span>
                      </div>
                    ) : null}
                  </div>
                </GroupItem>
              )}
              <GroupItem>
                {this.props.user.isOndemandSettlementEnabled &&
                this.props.user.isAllowedView('early_settlement') ? (
                  <Button.Primary
                    class="settle-btn btn-outline"
                    onClick={this.showOndemandSettlementForm}
                    disabled={current_balance.loading || current_balance.data.balance < 100}
                  >
                    Settle Now
                  </Button.Primary>
                ) : (
                  <Link className="pull-right" to="/settlements">
                    <span className="text-no-wrap" onClick={trackSettlementsClick}>
                      View Settlements
                    </span>
                  </Link>
                )}
              </GroupItem>
            </Group>
          </div>
        </Header>
        {/* </Sticky> */}
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
                        This graph helps you gain insights into your overall payments by seeing how
                        different payment methods stack up against each other in your revenue pool.
                      </p>
                      <div>
                        <span className="popover-highlight">Click tiles</span> to drill-down into
                        the hierarchy.
                      </div>
                      <div>
                        <span className="popover-highlight">Hover</span> to view information for
                        smaller tiles.
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
                  <p className="content-title section-title">{trafficSectionTitle}</p>
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
                  <p className="content-title section-title">{recentActivityTitle}</p>
                  <div className="content">
                    <RecentActivity
                      sectionTitle={recentActivityTitle}
                      onFetchPayments={onFetchPayments}
                      user={this.props.user}
                      currentBalance={current_balance}
                      onSelect={this.showOndemandSettlementForm}
                    />
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default AnalyticsDesktop;
