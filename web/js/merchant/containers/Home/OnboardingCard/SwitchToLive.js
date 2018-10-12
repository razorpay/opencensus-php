import React, { Component } from 'react';

import LocalStorageService from 'rzp/utils/localStorage';

import { LIVE_MODE } from './data';

export const switchToLive = merchantId => {
  if (!LocalStorageService.getItem(`hide-mode-dd-popover`)) {
    LocalStorageService.setItem(`show-mode-dd-popover`, 'true');
  }

  LocalStorageService.setItem(`rzp_mode--${merchantId}`, LIVE_MODE);
  window.location.reload();
};

export default class SwitchToLive extends Component {
  constructor(props) {
    super(props);

    this.switchToLive = this.switchToLive.bind(this);
  }

  switchToLive() {
    trackSwitchToLive(this.props.stepNum);
    switchToLive(props.merchantId);
  }

  render() {
    return (
      <a className="switch-to-live" onClick={this.switchToLive}>
        {this.props.children}
      </a>
    );
  }
}
