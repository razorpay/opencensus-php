import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'

import { updateFeatures } from 'merchant/modules/config'
import * as NotificationActions from 'merchant/modules/notifications'

@connect(
  (state) => {
    return {
      user: state.session.user,
      features: state.config.features,
      error: state.config.error
    }
  },
  { updateFeatures, ...NotificationActions }
)
export default class FlashCheckout extends Component {
  constructor() {
    super(...arguments)

    this.state = {
      config: {}
    }
    this.toggleFc = this.toggleFc.bind(this);
  }

  componentDidMount() {
    if (this.props.features.length) {
      this.parseAndSetFeatures(this.props.features);
    }
  }

  componentWillReceiveProps(nextProps) {
    if (!this.props.features.length && nextProps.features.length) {
      this.parseAndSetFeatures(nextProps.features);
    }
  }

  parseAndSetFeatures(features) {
    var noFlashCheckout = features.find(function (feature) {
      return feature.feature === 'noflashcheckout';
    });
    if(noFlashCheckout) {
      noFlashCheckout = noFlashCheckout.value;
    }
    this.setState({fcEnabled: !noFlashCheckout});
  }

  updateFeatures(fcEnabled) {
    var noFlashCheckout = !fcEnabled ? 1 : 0;
    var featureData = {
      features: {
        noflashcheckout: noFlashCheckout
      }
    };

    this.props.updateFeatures(featureData, this.props.user.current).then((res)=>{
      this.setState({
        features: res.data.features
      });
      this.props.showNotification({
        type: 'success',
        message: 'Your preference was saved'
      }, true)
    }).catch((err)=>{
      this.props.showNotification({
        type: 'danger',
        message: err.errors
      }, true)
    });
  }

  toggleFc() {
    const newFcEnabled = !this.state.fcEnabled
    this.setState({
      fcEnabled: newFcEnabled
    });

    this.updateFeatures(newFcEnabled);
  }

  render() {
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span>Flash Checkout</span>
          <label class=" pull-right">
            {
              this.state.fcEnabled ? (
                <span class="text-success">ENABLED</span>
              ) : (
                <span class="text-danger">DISABLED</span>
              )
            }
          </label>
        </div>
        <div class="panel-body">
          <form class="form-horizontal">
            <span class="help-block m-b-none">Securely save the card details of your customers, with Razorpay's Flash Checkout. </span>
            <div class="clearfix"></div>
            <div class="m-t">
                  <span class="help-block m-b-none pull-left">
                    <a class="highlight" target="_blank" href="https://razorpay.com/flashcheckout/">
                      Know more about Flash Checkout
                      <i class="fa fa-external-link" />
                    </a>
                  </span>
              <div class="pull-right">
                <div class=" text-center prev-next">
                  <button onClick={this.toggleFc} type="submit" class="btn btn-save btn-default btn-rounded ">
                    {
                      this.state.fcEnabled ? (
                        <span>Disable Flash Checkout</span>
                      ) : (
                        <span>Enable Flash Checkout</span>
                      )
                    }
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
        <footer class="panel-footer">
          {/*
           <div class="text-center m-t m-b">
           <alert ng-repeat="alert in alerts.getAlerts()" type="{{alert.type}}" close="alerts.closeAlert($index)">{{alert.msg}}</alert>
           </div>
           */}
        </footer>
      </div>
    );
  }
}
