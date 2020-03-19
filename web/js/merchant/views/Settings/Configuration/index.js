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
