import React, { Component } from 'react';

import { getItem, removeItem, setItem } from 'common/utils/localStorage';
import { switchMode } from '@libs/shared-utils';

import { LIVE_MODE, TEST_MODE } from './data';

export const switchToMode = (merchantId, mode = TEST_MODE, url = null) => {
  if (!getItem(`hide-mode-dd-popover`)) {
    removeItem(`hide-mode-dd-popover`);
    setItem(`show-mode-dd-popover`, 'true');
  }

  switchMode(merchantId, mode);

  if (url) {
    window.location = url;
  } else {
    window.location.reload();
  }
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
