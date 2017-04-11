import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { fetchConfigsFeatures, updateConfig, refreshConfig, uploadLogo, updateFeatures } from 'merchant/modules/config'
import * as NotificationActions from 'merchant/modules/notifications'
import FlashCheckout from './FlashCheckout'
import CheckoutTheme from './CheckoutTheme'
import EmailNotifications from './EmailNotifications'

@connect(
  (state) => {
    return {
      user: state.session.user,
      config: state.config.config,
      error: state.config.error
    }
  },
  { fetchConfigsFeatures, updateConfig, refreshConfig, uploadLogo, updateFeatures, ...NotificationActions }
)
export default class Congfiguration extends Component {
  constructor() {
    super(...arguments)

    this.saveConfig = this.saveConfig.bind(this);
  }

  componentWillMount() {
    this.props.fetchConfigsFeatures(this.props.user.current).catch((err)=>{
      this.props.showNotification({
        type: 'danger',
        message: err.errors
      }, true)
    });
  }

  /* Save Config*/
  saveConfig () {
    const config = this.props.config;
    const data = {
      'brand_color': config.brand_color ? config.brand_color.substr(1).toUpperCase() : null,
      'transaction_report_email': config.transaction_report_email ? config.transaction_report_email.split(',') : null
    };

    this.props.updateConfig(data).then((res) => {
      this.props.showNotification({
        type: 'success',
        message: 'Configuration Updated'
      }, true);
    }).catch((err) => {
      this.props.showNotification({
        type: 'danger',
        message: err.errors
      }, true)
    });
  }

  render() {
    return (
      <div class='react-root'>

        <div class="bg-light lter b-b wrapper-md">
          <h1 class="m-n font-thin h3">
            Configuration
            <spinner class="inline"></spinner>
          </h1>
        </div>

        <div class="wrapper-md col-sm-8 col-sm-offset-2" >
          <CheckoutTheme saveConfig={this.saveConfig}/>
          <FlashCheckout />
          <EmailNotifications saveConfig={this.saveConfig}/>
        </div>
      </div>
    )
  }
}

