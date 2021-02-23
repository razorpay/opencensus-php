import { Component } from 'react';
import { connect } from 'react-redux';
import Spinner from 'common/ui/Spinner';
import * as ConfigActions from 'merchant/reducers/config';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import FlashCheckout from './FlashCheckout';
import DefaultRefundSpeed from './DefaultRefundSpeed';
import CheckoutTheme from './CheckoutTheme';
import EmailNotifications from './EmailNotifications';
import PaymentSettings from './PaymentSettings';
import RTracking from 'react-tracking';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import InstantRefundFee from 'merchant/views/Transactions/Payments/components/InstantRefundFee';
import DebitRefundAnnouncement from '../../../components/Announcements/Refunds/DebitRefund';
import SmsNotification from './SmsNotification';
import WhatsappNotification from './WhatsappNotification';
import InternationalPayments from './InternationalPayments';
@connect(
  (state) => {
    return {
      user: state.session.user,
      refund_pricing: state.config.refund_pricing,
      configState: state.config,
      mode: state.session.mode,
    };
  },
  { ...ConfigActions, ...NotificationActions, openModal, closeModal },
)
@RTracking(() => window.rzpQ.component('CongfigurationContainer'))
export default class CongfigurationContainer extends Component {
  componentWillMount() {
    this.props.fetchFeatures(this.props.user.current).catch((err) => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
  }
  is_hash_loaded_once = false;
  saveConfig = ({ brand_color, transaction_report_email }) => {
    let data = {
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
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  componentDidMount() {}

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
        window.rzpAnalytics({
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
      user.contact_mobile &&
      user.activation_status === 'activated' &&
      user.role === 'owner'
    );
  };

  render() {
    const {
      mode,
      user,
      configState: { config, loading },
    } = this.props;

    let showInternationalPaymentsCard = false;
    if (mode === 'live') {
      if (user.activated_at < 1614105000) {
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
            <CheckoutTheme form="configForm" onSave={this.saveConfig} />
            {user.isOrgAllowedFunctionality('flashcheckout') && <FlashCheckout />}
            <PaymentSettings />
            <DefaultRefundSpeed />

            {showInternationalPaymentsCard && (
              <InternationalPayments user={user} mode={mode} config={config} />
            )}

            <EmailNotifications form="configForm" onSave={this.saveConfig} />
            {user.contact_mobile && <SmsNotification />}
            {this.isWhatsappNotificationEnabled(user) && <WhatsappNotification />}
          </div>
        )}
      </div>
    );
  }
}
