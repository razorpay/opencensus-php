import React from 'react';
import ReactDOM from 'react-dom';
import Popover, { PopoverBody } from 'common/ui/Popover';

const TOOLTIP_CLASS_NAME = 'no-merchant-logo';

class _MerchantLogoTooltip extends React.Component {
  state = { show: false };

  componentDidMount() {
    window.addEventListener('click', this.hideTooltip);
  }

  componentWillUnmount() {
    window.removeEventListener('click', this.hideTooltip);
  }

  hideTooltip = (e) => {
    if (e.target.className === TOOLTIP_CLASS_NAME) return;

    this.setState({
      show: false,
    });
  };

  handleTooltip = () => {
    // eslint-disable-next-line react/no-access-state-in-setstate
    this.setState({ show: !this.state.show });
  };

  render() {
    return (
      <div className={TOOLTIP_CLASS_NAME} onClick={this.handleTooltip}>
        Add your logo here
        {this.state.show && (
          <Popover align="bottom" theme="dark" persistent>
            <PopoverBody>
              Add your logo for better conversions through the
              <a
                target="_blank"
                href="/app/config"
                style={{ marginLeft: 4 }}
                rel="noreferrer noopener"
              >
                settings page. <i className="i i-external-link" />
              </a>
              <br />
              It can also be uploaded after completing this page.
            </PopoverBody>
          </Popover>
        )}
      </div>
    );
  }
}

export default function MerchantLogoTooltip() {
  // eslint-disable-next-line react/jsx-pascal-case
  return ReactDOM.createPortal(<_MerchantLogoTooltip />, document.getElementById('header-logo'));
}
