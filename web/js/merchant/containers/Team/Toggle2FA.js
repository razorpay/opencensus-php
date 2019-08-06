import { Component } from 'react';
import { connect } from 'react-redux';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import { toggle2FaEnforcement } from 'merchant/modules/team';

import { openModal, closeModal } from 'rzp/modules/modals';
import SwitchField from 'rzp/ui/Forms/SwitchField';
import {
  VerifyMobileNumber,
  MissingNumbers,
} from 'merchant/containers/Team/TwoFAModals';

@connect(
  state => {
    return {
      user: state.session.user,
      features: state.config.features,
      merchant: state.session.user,
    };
  },
  { updateFeatures, showNotification, openModal, closeModal }
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

  toggle2FA = e => {
    this.props.openModal({
      size: 'small',
      component: <NewInvite mobile="9886495755" count={6} {...this.props} />,
    });
  };

  render() {
    // this.toggle2FA()
    let { fcEnabled } = this.state;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">
            <i class="i i-phonelink-lock"></i> &nbsp; 2-Step verification to the
            team
          </span>
          <span class="toggler-btn">
            <SwitchField
              defaultChecked={!!fcEnabled}
              onChange={this.toggle2FA}
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
