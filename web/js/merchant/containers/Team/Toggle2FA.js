import { Component } from 'react';
import { connect } from 'react-redux';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchField from 'rzp/ui/Forms/SwitchField';

@connect(
  state => {
    return {
      user: state.session.user,
      features: state.config.features,
    };
  },
  { updateFeatures, showNotification }
)
export default class Toggle2FA extends Component {
  constructor(props) {
    super(props);
    this.state = {};

    if (props.features.length) {
      const fcEnabled = this.getToggle2FAFlag(props.features);
      this.state.fcEnabled = fcEnabled;
    }
  }

  componentWillReceiveProps(nextProps) {
    if (!this.props.features.length && nextProps.features.length) {
      const fcEnabled = this.getToggle2FAFlag(nextProps.features);

      this.setState({ fcEnabled });
    }
  }

  getToggle2FAFlag(features) {
    let noToggle2FA =
      features.find(feature => feature.feature === 'noToggle2FA') || {};

    const fcEnabled = !noToggle2FA.value;
    return fcEnabled;
  }

  analytics = action => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - Flash Checkout`,
    });
  };

  toggleFc = (enableFC, cb) => {
    let shouldSync = 1;
    var data = {
      features: {
        noToggle2FA: enableFC ? 0 : 1,
      },
      should_sync: shouldSync,
    };

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then(res => {
        cb(true);

        if (enableFC) {
          this.analytics('Enable');
        } else {
          this.analytics('Disable');
        }
        this.props.showNotification({
          type: 'success',
          message: 'Your preference was saved',
        });
        this.setState({
          fcEnabled: enableFC,
        });
      })
      .catch(err => {
        cb(false);

        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    let { fcEnabled } = this.state;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">2-Step verification to the team</span>

          <span class="toggler-btn">
            <SwitchField
              defaultChecked={!!fcEnabled}
              onChange={(isChecked, cb) => this.toggleFc(isChecked, cb)}
              type="prime"
            />
            {fcEnabled ? (
              <b class="text-primary">Enabled</b>
            ) : (
              <b className="text-faded">Disabled</b>
            )}
          </span>
        </div>

        <div class="panel-body">
          <form class="form-horizontal">
            <div class="description">
              <p>
                2-step verification will be enforced to all the team members who
                have access to this Dashboard.
              </p>
              <p>
                <strong>Note:</strong> This setting requires 2-step verification
                set up on your account
              </p>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
