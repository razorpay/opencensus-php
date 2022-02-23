import { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import Spinner from 'common/ui/Spinner';
import * as ConfigActions from 'merchant/reducers/config';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import FlashCheckout from './FlashCheckout';
import DefaultRefundSpeed from './DefaultRefundSpeed';
import FeeBearerSelfserver from './FeeBearerSelfserve';
import CheckoutTheme from './CheckoutTheme';
import EmailNotifications from './EmailNotifications';
import PaymentSettings from './PaymentSettings';
import RTracking from 'react-tracking';
import ShowWhen from 'merchant/components/ShowWhen';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { merchantFetch } from 'merchant/utils/ajax';
import InstantRefundFee from 'merchant/views/Transactions/Payments/components/InstantRefundFee';
import DebitRefundAnnouncement from '../../../components/Announcements/Refunds/DebitRefund';
import SmsNotification from './SmsNotification';
import WhatsappNotification from './WhatsappNotification';
import InternationalPayments from './InternationalPayments';
import { fetchUser } from 'merchant/reducers/session';
import CovidKnowMore from 'common/ui/CovidKnowMore';
import IntoView from 'common/ui/IntoView';
import rolesList from 'merchant/helpers/permissions/roles-list';
import TokenisationConsent from './TokenisationConsent';
import {
  FLASH_CHECKOUT,
  CAPTURE_SETTINGS,
  EMAIL_NOTIF,
  SMS_NOTIF,
  REFUND_SETTINGS,
  WHATSAPP_NOTIF,
} from './deeplink-constants';
import { getFeature } from 'common/utils/features';
import EasterEgg from 'merchant/components/EasterEgg';
import Firc from './components/FircAnnouncements/Firc';

class CongfigurationContainer extends Component {
  state = {
    isLoading: false,
    isPaypalOrg: false,
    isPaypalMid: false,
  };

  componentWillMount() {
    this.props.fetchFeatures(this.props.user.current).catch((err) => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });

    // check paypal org feature
    if (this.props.org.features.indexOf('axis_paypal') > -1) {
      this.setState({
        isPaypalOrg: true,
      });

      // check paypal MID feature
      this.props
        .fetchFeatureStatus(this.props.user.id, 'axis_paypal_enable')
        .then((fetchFeatureStatusResp) => {
          if (fetchFeatureStatusResp.data.status) {
            this.setState({
              isPaypalMid: true,
            });
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
    }
  }

  is_hash_loaded_once = false;
  saveConfig = ({ brand_color, transaction_report_email }, config) => {
    const data = {
      brand_color: brand_color ? brand_color.substr(1).toUpperCase() : null,
      transaction_report_email: transaction_report_email
        ? transaction_report_email.split(',')
        : null,
    };

    return this.props
      .updateConfig(data)
      .then((res) => {
        this.props.showNotification({
          type: 'success',
          message: 'Configuration Updated',
          hidePrevious: true,
        });
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
    if (this.props.location.hash === '#instantfee' && !this.props.refund_pricing.not_loaded) {
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

  isWhatsappNotificationEnabled = (user) => {
    return (
      user.isWhatsappNotificationEnabled() &&
      user.user &&
      user.user.contact_mobile &&
      user.activation_status === 'activated' &&
      (user.role === rolesList.OWNER || user.role === rolesList.ADMIN)
    );
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

  shouldShowConsentForTokenisation = () => {
    const showConsent = getFeature(this.props.features, 'disable_collect_consent');
    return Object.keys(showConsent).length > 0;
  };
  render() {
    const {
      mode,
      user,
      configState: { config, loading, paypal_terminals },
      org,
    } = this.props;
    let showInternationalPaymentsCard = false;
    if (mode === 'live') {
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
      <div class="content-wrapper content-sm" id="settings-content">
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div>
            <CheckoutTheme
              form="configForm"
              onSave={this.saveConfig}
              onSwitchChange={this.handleCovidReliefOptinAndOut}
              isLoading={this.state.isLoading}
            />
            <ShowWhen additionalCondition={this.shouldShowConsentForTokenisation}>
              <TokenisationConsent />
            </ShowWhen>
            {user.isOrgAllowedFunctionality('flashcheckout') && (
              <IntoView hashedWith={FLASH_CHECKOUT}>
                <FlashCheckout org={org} />
              </IntoView>
            )}
            <IntoView hashedWith={CAPTURE_SETTINGS}>
              <PaymentSettings org={org} />
            </IntoView>
            <IntoView hashedWith={REFUND_SETTINGS}>
              <DefaultRefundSpeed org={org} />
            </IntoView>
            {user?.international && <Firc />}
            <ShowWhen
              additionalCondition={(usr) =>
                usr.isOrgRZP &&
                usr.isAccepted &&
                usr.role === 'owner' &&
                !usr.international &&
                !usr.isPayPalEnabled &&
                usr.isFeeBearerSelfServeOn
              }
            >
              <FeeBearerSelfserver />
            </ShowWhen>
            {mode === 'live' && showInternationalPaymentsCard && (
              <InternationalPayments
                user={user}
                mode={mode}
                config={config}
                org={org}
                paypal_terminals={paypal_terminals}
              />
            )}
            <IntoView hashedWith={EMAIL_NOTIF}>
              <EmailNotifications form="configForm" onSave={this.saveConfig} />
            </IntoView>
            {user.contact_mobile && (
              <IntoView hashedWith={SMS_NOTIF}>
                <SmsNotification />
              </IntoView>
            )}
            {this.isWhatsappNotificationEnabled(user) && (
              <IntoView hashedWith={WHATSAPP_NOTIF}>
                <WhatsappNotification />
              </IntoView>
            )}
          </div>
        )}
        <EasterEgg extraClass="ftx-settings-page-mweb" page="Settings" />
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
        features: state.config.features,
      };
    },
    { ...ConfigActions, ...NotificationActions, openModal, closeModal, fetchUser },
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('CongfigurationContainer')),
)(CongfigurationContainer);
