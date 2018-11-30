import { Component } from 'react';

export default class SupportHeader extends Component {
  render() {
    const { isOpen, onToggle } = this.props;

    return (
      <div class="support">
        <button
          class={`support-header btn-primary ${isOpen ? 'open' : ''}`}
          onClick={onToggle}
        >
          <span class="pull-left">
            <i class="i i-headset m-r" />
            Help and Support
          </span>
          <span class="support-toggle pull-right" />
        </button>
      </div>
    );
  }
}
