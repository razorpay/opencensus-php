import { Component } from 'react';
import LazyLoad from 'react-lazyload';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { compose } from 'redux';

import { withI18Service } from 'common/i18';
import CovidKnowMore from 'common/ui/CovidKnowMore';
import IntoView from 'common/ui/IntoView';
import LoaderDots from 'common/ui/LoaderDots';
import Spinner from 'common/ui/Spinner';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import DebitRefundAnnouncement from 'merchant/components/Announcements/Refunds/DebitRefund';
import EasterEgg from 'merchant/components/EasterEgg';
import ShowWhen from 'merchant/components/ShowWhen';
import * as ConfigActions from 'merchant/reducers/config';
import { fetchUser } from 'merchant/reducers/session';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  isFlashCheckoutAllowed,
  isSkipMandatorySummaryPageAllowed,
  isWhatsappNotificationEnabled,
  isSmsNotificationEnabled,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { showDynamicFields } from 'merchant/views/PaymentLinks/utils';
import InstantRefundFee from 'merchant/views/Transactions/v1/Payments/components/InstantRefundFee';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';

import { CheckoutConfigExperiment } from './CheckoutConfig';
import { CheckoutEditorExperiment } from './CheckoutEditor';
import DefaultRefundSpeed from './DefaultRefundSpeed';
import EmailNotifications from './EmailNotifications';
import FeeBearerSelfserver from './FeeBearerSelfserve';
import InternationalPayments from './InternationalPayments';
import MissedOrderPaymentLink from './MissedOrderPaymentLink';
import PaymentSettings from './PaymentSettings';
import SmsNotification from './SmsNotification';
import ToggleSetting from './ToggleSetting';
import WhatsappNotification from './WhatsappNotification';
import Firc from './components/FircAnnouncements/Firc';
import {
  FLASH_CHECKOUT,
  CAPTURE_SETTINGS,
  EMAIL_NOTIF,
  SMS_NOTIF,
  REFUND_SETTINGS,
  WHATSAPP_NOTIF,
  SKIP_CARD_MANDATE_SUMMARY,
  MISSED_ORDER_PAYMENT_LINK,
  ACCOUNT_SETTINGS,
  DYNAMIC_FIELDS_PL,
} from './deeplink-constants';
import DynamicFieldsPl from './dynamicFieldsPl';
import { flashCheckoutProps, skipCardMandateSummaryProps } from './settings-config-constants';

// eslint-disable-next-line react/no-unsafe
class CongfigurationContainer extends Component {
  constructor(props) {
    super(props);
    const {
      configState: { features, error },
    } = this.props;
    this.state = {
      isLoading: false,
      isPaypalOrg: false,
      isPaypalMid: false,
      allowCFBInternational: false,
      isFeaturesLoading: !features.length || error,
    };
  }

  UNSAFE_componentWillMount() {
    const {
      showFeeBearer,
      showInternationalPayments,
      configState: { features, error },
    } = this.props;
    if (!features.length || error) {
      this.props
        .fetchFeatures(this.props.user.current)
        .catch((err) => {
          this.props.showNotification({
            type: 'error',
            message: err.errors,
          });
        })
        .finally(() => {
          this.setState({ isFeaturesLoading: false });
        });
    }

    // check paypal org feature
    if (showInternationalPayments && this.props.org.features.indexOf('axis_paypal') > -1) {
      this.setState({
        isPaypalOrg: true,
      });

      // check paypal MID feature
      this.fetchFeatureFlagStatus('axis_paypal_enable', 'isPaypalMid');
    }
    if (showFeeBearer) {
      this.fetchFeatureFlagStatus('allow_cfb_international', 'allowCFBInternational');
    }
  }

  componentDidMount() {
    this.props.fetchRefundPricing();
  }

  is_hash_loaded_once = false;
  saveConfig = ({ brand_color, transaction_report_email }, config) => {
    const data = {};
    if (brand_color) {
      data.brand_color = brand_color.substr(1).toUpperCase();
    }

    if (transaction_report_email) {
      data.transaction_report_email = transaction_report_email.split?.(',');
    }

    return this.props
      .updateConfig(data)
      .then((res) => {
        this.props.showNotification({
          type: 'success',
          message: 'Configuration Updated',
          hidePrevious: true,
        });
        if (data.brand_color) {
          selfServeTrackSuccess({
            selfServeAction: 'Theme Color Changed',
            page: 'Config',
            screen: 'Settings',
          });
        }
        if (config === 'theme') {
          analyticsTrack({
            objectName: 'theme color save changes',
            actionName: 'result',
            screen: 'settings',
            properties: {
              location: 'configuration',
              status: 'Success',
              colorCode: res.data.brand_color,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        } else {
          analyticsTrack({
            objectName: 'save email notifications',
            actionName: 'result',
            screen: 'settings',
            properties: {
              location: 'configuration',
              status: 'Success',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        }
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
        if (config === 'theme') {
          analyticsTrack({
            objectName: 'theme color save changes',
            actionName: 'result',
            screen: 'settings',
            properties: {
              location: 'configuration',
              status: 'Failure',
              failureReason: err.errors[0],
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        } else {
          analyticsTrack({
            objectName: 'save email notifications',
            actionName: 'result',
            screen: 'settings',
            properties: {
              location: 'configuration',
              status: 'Failure',
              failureReason: err.errors[0],
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        }
      });
  };

  componentDidUpdate() {
    this.popupIfSettle();
  }

  scrolltoIR = () => {
    setTimeout(() => {
      this.scrollIntoView('default-refund-container');
      const el = document.getElementById('instant-refund-panel-col');
      if (el) {
        el.style.border = '1px solid #528ff0';
        setTimeout(() => {
          el.style.border = '1px solid #ebeff0';
        }, 2000);
      }
    }, 1000);
  };

  popupIfSettle = () => {
    if (this.props.location.hash === '#paypalonboard') {
      this.resetHash();
      setTimeout(() => this.scrollIntoView('paypal-auto-onboarding'), 1000);
    }

    if (this.props.location.hash === '#debitrefund') {
      this.resetHash();
      this.props.openModal({
        component: (
          <DebitRefundAnnouncement
            onClose={this.props.closeModal}
            onSuccess={() => {
              console.log('success!');
              this.props.closeModal();
              this.scrolltoIR();
            }}
          />
        ),
        size: 'large',
      });
    }

    if (this.props.location.hash === '#instantrefunds') {
      if (!this.is_hash_loaded_once) {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Instant Refund',
          eventAction: 'Enable Now',
          eventLabel: `Announcement | Enable Now`,
        });
        this.props.tracking.trackEvent(
          window.rzpQ.merchantActions().initiated(`Click - Enable Now`, {
            label: 'Announcement Tab',
            session_id: window.session_id,
            category: 'Merchant Dashboard - IR',
          }),
        );
        this.is_hash_loaded_once = true;
      }

      this.scrolltoIR();
    }
    if (this.props.location.hash === '#instantfee' && !this.props.refund_pricing?.not_loaded) {
      this.props.openModal({
        component: <InstantRefundFee pricing={this.props.refund_pricing} />,
        size: 'small',
      });
      this.resetHash();
    }
  };

  scrollIntoView = (id) => {
    const el = document.getElementById(id);
    if (el) {
      el.scrollIntoView();
      el.click();
    }
  };

  resetHash = () => {
    this.props.history.push({
      pathname: this.props.history.location.pathname,
      hash: '',
    });
  };

  handleCovidReliefOptinAndOut = (e) => {
    this.setState({ isLoading: true });
    const bool = e ? 1 : 0;

    merchantFetch({
      url: `merchants/me/features?features[covid_19_relief]=${bool}`,
      mode: `${this.props.mode}`,
      method: 'POST',
    })
      .then(() => {
        this.props.fetchUser().then(() => {
          this.props.showNotification({
            type: 'success',
            message: 'Updated preferences',
          });
          this.setState({ isLoading: false });
          if (bool === 1) {
            this.props.openModal({
              size: 'medium',
              component: <CovidKnowMore isCovidDonations />,
            });
          }
        });
      })
      .catch((err) => {
        this.setState({ isLoading: false });
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
        this.props.fetchUser();
      });
  };

  fetchFeatureFlagStatus = (flag, state) => {
    this.props
      .fetchFeatureStatus(this.props.user?.id, flag)
      .then((response) => {
        if (response?.success) {
          this.setState((prevState) => ({
            ...prevState,
            [state]: response?.data?.status,
          }));
        }
      })
      .catch((err) => {
        if (err) {
          this.props.showNotification({
            type: 'error',
            message: err.errors[0],
          });
        }
      });
  };

  /**
   * Check conditions to show Fee Bearer Self Serve
   * Allow international merchants to use fee bearer self serve if they have allow_cfb_international flag set to true
   *
   * @param {*} user - User Object
   * @returns {Boolean} true if conditions are met
   */
  shouldShowFeeBearerSelfServe = ({
    isOrgRZP,
    isAccepted,
    role,
    isFeeBearerSelfServeOn,
    isPayPalEnabled,
    international,
  }) => {
    const { allowCFBInternational } = this.state;
    const { showFeeBearer } = this.props;
    const shouldAllow =
      showFeeBearer &&
      isOrgRZP &&
      isAccepted &&
      role === 'owner' &&
      isFeeBearerSelfServeOn &&
      !isPayPalEnabled;

    if (allowCFBInternational) {
      return shouldAllow && international;
    }

    return shouldAllow && !international;
  };

  render() {
    const {
      mode,
      user,
      configState: { config, loading: isConfigLoading, paypal_terminals },
      org,
      showBranding,
      showStyling,
      showFeatures,
      showPaymentConfiguration,
      showMissedOrderPaymentLink,
      showFlashCheckout,
      showPaymentSettings,
      showDefaultRefundSpeed,
      showFirc,
      showInternationalPayments,
      showEmailNotifications,
      showSmsNotifications,
      showWhatsappNotifications,
      showSkipMandatorySummaryPage,
      showAnnouncements,
      className,
      isOldFlow,
      i18: { isConfigTagEnabled },
    } = this.props;
    const { isFeaturesLoading } = this.state;
    let showInternationalPaymentsCard = false;
    const extraConfig = { isConfigTagEnabled };
    const remarketerEnabled = user.isFeatureEnabled('missed_orders_plink');
    if (mode === 'live' && showInternationalPayments) {
      if (this.state.isPaypalOrg) {
        if (this.state.isPaypalMid) {
          showInternationalPaymentsCard = true;
        } else {
          showInternationalPaymentsCard = false;
        }
      } else if (user.activated_at < 1614105000) {
        // Show international payments card if merchant was activated before 24 February 2021 12:00:00 AM GMT+05:30
        showInternationalPaymentsCard = true;
      } else if (user.internationalActivationFlow.isWhitelistFlow) {
        if (!user.isAccepted) {
          // Show international payments card only if merchant's IAF is whitelisted and L1 activated
          showInternationalPaymentsCard = true;
        }
      }
    }
    return (
      <div className={['content-wrapper content-sm', className].join(' ')} id="settings-content">
        {isConfigLoading || isFeaturesLoading ? (
          isOldFlow ? (
            <div className="page-spinner-container">
              <Spinner />
            </div>
          ) : (
            <LoaderDots />
          )
        ) : (
          <div>
            {showBranding && (
              <IntoView hashedWith={ACCOUNT_SETTINGS}>
                <CheckoutConfigExperiment
                  form="configForm"
                  onSave={this.saveConfig}
                  onSwitchChange={this.handleCovidReliefOptinAndOut}
                  isLoading={this.state.isLoading}
                />
              </IntoView>
            )}
            {showStyling && (
              <IntoView hashedWith={ACCOUNT_SETTINGS}>
                <CheckoutEditorExperiment showStyling={showStyling} />
              </IntoView>
            )}
            {showFeatures && (
              <IntoView hashedWith={ACCOUNT_SETTINGS}>
                <CheckoutEditorExperiment showFeatures={showFeatures} extraConfig={extraConfig} />
              </IntoView>
            )}
            {showPaymentConfiguration && (
              <IntoView hashedWith={ACCOUNT_SETTINGS}>
                <CheckoutEditorExperiment showPaymentConfiguration extraConfig={extraConfig} />
              </IntoView>
            )}
            {showMissedOrderPaymentLink && remarketerEnabled && (
              <IntoView hashedWith={MISSED_ORDER_PAYMENT_LINK}>
                <MissedOrderPaymentLink />
              </IntoView>
            )}
            <ShowWhen
              additionalCondition={(user) =>
                showFlashCheckout && isFlashCheckoutAllowed(user, extraConfig)
              }
            >
              <IntoView hashedWith={FLASH_CHECKOUT}>
                <ToggleSetting {...flashCheckoutProps} org={org} />
              </IntoView>
            </ShowWhen>

            <ShowWhen
              additionalCondition={() =>
                showPaymentSettings && !isConfigTagEnabled('account.payment_capture')
              }
            >
              <IntoView hashedWith={CAPTURE_SETTINGS}>
                <PaymentSettings org={org} />
              </IntoView>
            </ShowWhen>

            <ShowWhen
              additionalCondition={() =>
                showDefaultRefundSpeed && !isConfigTagEnabled('refunds.refund')
              }
            >
              <IntoView hashedWith={REFUND_SETTINGS}>
                <DefaultRefundSpeed org={org} />
              </IntoView>
            </ShowWhen>

            <ShowWhen
              additionalCondition={(user) =>
                showFirc && !isConfigTagEnabled('settings.international') && user?.international
              }
            >
              <Firc />
            </ShowWhen>

            <ShowWhen additionalCondition={this.shouldShowFeeBearerSelfServe}>
              <FeeBearerSelfserver />
            </ShowWhen>

            <ShowWhen
              additionalCondition={(user) =>
                !isConfigTagEnabled('settings.international') &&
                user.international &&
                mode === 'live' &&
                showInternationalPaymentsCard
              }
            >
              <InternationalPayments
                user={user}
                mode={mode}
                config={config}
                org={org}
                paypal_terminals={paypal_terminals}
              />
            </ShowWhen>

            {showEmailNotifications && (
              <IntoView hashedWith={EMAIL_NOTIF}>
                <EmailNotifications form="configForm" onSave={this.saveConfig} />
              </IntoView>
            )}

            <ShowWhen
              additionalCondition={(user) => showSmsNotifications && isSmsNotificationEnabled(user)}
            >
              <IntoView hashedWith={SMS_NOTIF}>
                <SmsNotification />
              </IntoView>
            </ShowWhen>

            <ShowWhen
              additionalCondition={(user) =>
                showWhatsappNotifications && isWhatsappNotificationEnabled(user, extraConfig)
              }
            >
              <IntoView hashedWith={WHATSAPP_NOTIF}>
                <WhatsappNotification />
              </IntoView>
            </ShowWhen>

            <ShowWhen
              additionalCondition={() =>
                showSkipMandatorySummaryPage && isSkipMandatorySummaryPageAllowed(extraConfig)
              }
            >
              <IntoView hashedWith={SKIP_CARD_MANDATE_SUMMARY}>
                <ToggleSetting {...skipCardMandateSummaryProps} />
              </IntoView>
            </ShowWhen>

            <ShowWhen additionalCondition={() => showDynamicFields()}>
              <LazyLoad height={300} offset={50} once>
                <IntoView hashedWith={DYNAMIC_FIELDS_PL}>
                  <DynamicFieldsPl />
                </IntoView>
              </LazyLoad>
            </ShowWhen>
          </div>
        )}

        <ShowWhen
          additionalCondition={() =>
            showAnnouncements && !isConfigTagEnabled('announcements.announcements')
          }
        >
          <EasterEgg extraClass="ftx-settings-page-mweb" page="Settings" />
        </ShowWhen>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        user: state.session.user,
        refund_pricing: state.config.refund_pricing,
        configState: state.config,
        mode: state.session.mode,
        org: state.session.org,
      };
    },
    {
      ...ConfigActions,
      ...NotificationActions,
      openModal,
      closeModal,
      fetchUser,
    },
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('CongfigurationContainer')),
)(withI18Service(CongfigurationContainer));
