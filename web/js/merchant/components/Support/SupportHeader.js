import { Component } from 'react';

import { classList } from 'common/util';

export default class SupportHeader extends Component {
  render() {
    const { notifyCount = 0, isOpened, onToggle } = this.props;

    return (
      <div
        class={classList('support-launcher', isOpened && 'active')}
        onClick={onToggle}
      >
        {notifyCount ? <span class="notify-icon">{notifyCount}</span> : null}
        <div class="open-icon">
          <i class="i i-headset" />
        </div>
        <div class="close-icon">
          <i class="i i-close " />
        </div>
      </div>
    );
  }
}
