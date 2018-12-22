import { Component } from 'react';

import { classList } from 'common/util';

export default class SupportHeader extends Component {
  render() {
    const { notifyCount, isOpened, onToggle } = this.props;

    return (
      <button
        class={classList(
          'btn btn-primary btn-block support-header',
          notifyCount && 'notify'
        )}
        onClick={onToggle}
      >
        <span class="pull-left">
          <i class="i i-headset m-r" />
          Help and Support
          {notifyCount > 0 &&
            !isOpened && <span class="support-notify m-l">{notifyCount}</span>}
        </span>
        <i
          class={`support-toggle pull-right i ${
            isOpened ? 'i-close' : 'i-chevron-up'
          }`}
        />
      </button>
    );
  }
}
