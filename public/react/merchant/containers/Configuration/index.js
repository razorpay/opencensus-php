import { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';
import Spinner from 'rzp/ui/Spinner';
import * as ConfigActions from 'merchant/modules/config';
import * as NotificationActions from 'merchant/modules/notifications';
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
    this.props.fetchConfigAndFeatures(this.props.user.current).catch(err => {
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
    let { config, features, loading, error } = this.props.configState;

    return (
      <div class="react-root">
        <Header title="Configuration" showMode={false} />

        {loading
          ? <div class="page-spinner-container">
              <Spinner />
            </div>
          : <div class="content-wrapper">
              <div class="row">
                <div class="col-md-8 col-md-offset-2 col-sm-12">
                  <CheckoutTheme form="configForm" onSave={this.saveConfig} />
                  <FlashCheckout />
                  <EmailNotifications
                    form="configForm"
                    onSave={this.saveConfig}
                  />
                </div>
              </div>
            </div>}
      </div>
    );
  }
}
