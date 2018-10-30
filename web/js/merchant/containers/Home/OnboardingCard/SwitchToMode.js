import React, { Component } from 'react';

import LocalStorageService from 'rzp/utils/localStorage';

import { LIVE_MODE, TEST_MODE } from './data';

export const switchToMode = (merchantId, mode = TEST_MODE) => {
  if (!LocalStorageService.getItem(`hide-mode-dd-popover`)) {
    LocalStorageService.removeItem(`hide-mode-dd-popover`);
    LocalStorageService.setItem(`show-mode-dd-popover`, 'true');
  }

  LocalStorageService.setItem(`rzp_mode--${merchantId}`, mode);
  window.location.reload();
};

export default class SwitchToMode extends Component {
  constructor(props) {
    super(props);

    this.switchToMode = this.switchToMode.bind(this);
  }

  switchToMode() {
    switchToMode(this.props.merchantId, this.props.mode);

    return this.props.onSwitch && this.props.onSwitch(this.props.mode);
  }

  render() {
    return (
      <a className="switch-to-mode" onClick={this.switchToMode}>
        {this.props.children}
      </a>
    );
  }
}

SwitchToMode.defaultProps = {
  mode: TEST_MODE,
};
