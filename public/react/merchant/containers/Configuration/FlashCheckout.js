import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';

@connect(
  state => {
    return {
      user: state.session.user,
      features: state.config.features,
    };
  },
  { updateFeatures, showNotification }
)
export default class FlashCheckout extends Component {
  state = {
    fcEnabled: false,
  };

  componentDidMount() {
    if (this.props.features.length) {
      this.setFlashCheckoutFlag(this.props.features);
    }
  }

  componentWillReceiveProps(nextProps) {
    if (!this.props.features.length && nextProps.features.length) {
      this.setFlashCheckoutFlag(nextProps.features);
    }
  }

  setFlashCheckoutFlag(features) {
    let noFlashCheckout = features.find(
      feature => feature.feature === 'noflashcheckout'
    ) || {};

    this.setState({ fcEnabled: !noFlashCheckout.value });
  }

  toggleFc = () => {
    let fcEnabled  = this.state.fcEnabled;
    let shouldSync = 1;
    var data = {
      features: {
        noflashcheckout: fcEnabled ? 1 : 0,
      },
      should_sync: shouldSync
    };

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then(res => {
        this.props.showNotification({
          type: 'success',
          message: 'Your preference was saved',
        });
        this.setState({
          fcEnabled: !fcEnabled,
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
    let { fcEnabled } = this.state;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span>Flash Checkout</span>
          <label class="pull-right">
            {fcEnabled
              ? <span class="text-success">ENABLED</span>
              : <span class="text-danger">DISABLED</span>}
          </label>
        </div>

        <div class="panel-body">
          <form class="form-horizontal">
            <span class="help-block">
              Securely save the card details of your customers, with Razorpay's Flash Checkout.
            </span>

            <div class="form-group">
              <div class="col-sm-10">
                <a
                  class="highlight"
                  target="_blank"
                  href="https://razorpay.com/flashcheckout/"
                >
                  Know more about Flash Checkout
                  <i
                    class="icon icon-external-link"
                    style={{ marginLeft: '5px' }}
                  />
                </a>
              </div>
              <div class="col-sm-2">
                <AsyncButton
                  class="btn btn-default pull-right"
                  text={
                    fcEnabled
                      ? 'Disable Flash Checkout'
                      : 'Enable Flash Checkout'
                  }
                  pendingText={fcEnabled ? 'Disabling...' : 'Enabling...'}
                  onClick={this.toggleFc}
                />
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
