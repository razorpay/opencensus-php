import { Component } from 'react';

export default class SupportHeader extends Component {
  render() {
    const { notifyCount, isOpened, onToggle } = this.props;

    return (
      <button class="btn-primary btn-block support-header" onClick={onToggle}>
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
