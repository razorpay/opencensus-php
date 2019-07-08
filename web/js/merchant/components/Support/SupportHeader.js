import { Component } from 'react';
import Popover, { PopoverBody } from 'rzp/ui/Popover';

import LocalStorageService from 'rzp/utils/localStorage';
import { classList } from 'common/util';

export default class SupportHeader extends Component {
  constructor(props) {
    super(props);

    const hasInteractedBefore = LocalStorageService.getItem(
      'show-support-helper'
    );
    this.state = {
      showHelpTooltip: !hasInteractedBefore,
    };
  }

  componentDidUpdate(prevProps) {
    if (
      this.state.showHelpTooltip &&
      prevProps.isOpened !== this.props.isOpened &&
      this.props.isOpened
    ) {
      LocalStorageService.setItem('show-support-helper', 'true');

      this.setState({
        showHelpTooltip: false,
      });
    }
  }

  render() {
    const { notifyCount = 0, isOpened, onToggle } = this.props;

    return (
      <div
        class={classList('support-launcher', isOpened && 'active')}
        onClick={onToggle}
      >
        <span className="help-content">
          {notifyCount ? <span class="notify-icon">{notifyCount}</span> : null}
          <div class="open-icon">
            <i class="i i-headset" />
          </div>
          <div class="close-icon">
            <i class="i i-close " />
          </div>

          <Popover
            align="left"
            theme="dark"
            persistent={this.state.showHelpTooltip}
          >
            <PopoverBody>
              <div>
                To know how to use the Dashboard, read the Dashboard Guide
              </div>
            </PopoverBody>
          </Popover>
        </span>
      </div>
    );
  }
}
