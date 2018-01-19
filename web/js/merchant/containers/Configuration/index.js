import { Component } from 'react';
import { connect } from 'react-redux';
import Spinner from 'rzp/ui/Spinner';
import * as ConfigActions from 'merchant/modules/config';
import * as NotificationActions from 'rzp/modules/notifications';
import FlashCheckout from './FlashCheckout';
import CheckoutTheme from './CheckoutTheme';
import EmailNotifications from './EmailNotifications';

@connect(
  state => {
    return {
      user: state.session.user,
      configState: state.config,
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
          type: 'danger',
          message: err.errors,
        });
      });
  };

  render() {
    let { config, features, loading } = this.props.configState;

    return (
      <div class="content-wrapper content-sm">
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div>
            <CheckoutTheme form="configForm" onSave={this.saveConfig} />
            <FlashCheckout />
            <EmailNotifications form="configForm" onSave={this.saveConfig} />
          </div>
        )}
      </div>
    );
  }
}
