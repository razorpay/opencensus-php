import { Component } from 'react';
import { connect } from 'react-redux';
import Spinner from 'common/ui/Spinner';
import * as ConfigActions from 'merchant/reducers/config';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import FlashCheckout from './FlashCheckout';
import DefaultRefundSpeed from './DefaultRefundSpeed';
import Internationalization from './Internationalization';
import CheckoutTheme from './CheckoutTheme';
import EmailNotifications from './EmailNotifications';
import InternationalConfig from './InternationalConfig';
import PaypalOnboarding from './PaypalOnboarding';
import { showWhenUtil } from 'merchant/components/ShowWhen';

@connect(
  state => {
    return {
      user: state.session.user,
      configState: state.config,
      mode: state.session.mode,
    };
  },
  { ...ConfigActions, ...NotificationActions }
)
export default class CongfigurationContainer extends Component {
  componentWillMount() {
    this.props.fetchFeatures(this.props.user.current).catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
  }

  saveConfig = ({ brand_color, transaction_report_email }) => {
    let data = {
      brand_color: brand_color ? brand_color.substr(1).toUpperCase() : null,
      transaction_report_email: transaction_report_email
        ? transaction_report_email.split(',')
        : null,
    };

    return this.props
      .updateConfig(data)
      .then(res => {
        this.props.showNotification({
          type: 'success',
          message: 'Configuration Updated',
          hidePrevious: true,
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  componentDidUpdate() {
    this.popupIfSettle();
  }

  popupIfSettle() {
    if (this.props.location.hash === '#paypalonboard') {
      this.resetHash();
      setTimeout(this.scrollIntoView, 1000);
    }
  }

  scrollIntoView() {
    const el = document.getElementById('paypal-onboard');
    if (el) {
      el.scrollIntoView();
      el.click();
    }
  }

  resetHash = () => {
    this.props.history.push({
      pathname: this.props.history.location.pathname,
      hash: '',
    });
  };

  render() {
    let { config, features, loading } = this.props.configState;

    return (
      <div class="content-wrapper content-sm" id="settings-content">
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div>
            <CheckoutTheme form="configForm" onSave={this.saveConfig} />
            {this.props.user.isOrgAllowedFunctionality('flashcheckout') && (
              <FlashCheckout />
            )}
            {this.props.user.isActivated &&
            this.props.mode === 'live' &&
            showWhenUtil({ featureEnabled: 'offers' }) ? (
              <PaypalOnboarding />
            ) : null}
            <DefaultRefundSpeed />
            {/* Hiding old International Flow. TODO: Remove permanently */}
            {/* temporarily hide internationalization for test mode due to inconsistency in db */}
            {/* {this.props.mode === 'live' && <Internationalization />} */}
            {this.props.mode === 'live' && <InternationalConfig />}
            <EmailNotifications form="configForm" onSave={this.saveConfig} />
          </div>
        )}
      </div>
    );
  }
}
