import ReactDOM from 'react-dom';
import Popover, { PopoverBody } from 'common/ui/Popover';

class _MerchantLogoTooltip extends React.Component {
  state = { show: false };

  handleTooltip = () => {
    this.setState({ show: !this.state.show });
  };

  render() {
    return (
      <div class="no-merchant-logo" onClick={this.handleTooltip}>
        Add your logo here
        {this.state.show && (
          <Popover align="bottom" theme="dark" persistent>
            <PopoverBody>
              Add your logo for better conversions through the
              <a target="_blank" href="/app/config" style={{ marginLeft: 4 }}>
                settings page. <i class="i i-external-link" />
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
  return ReactDOM.createPortal(<_MerchantLogoTooltip />, document.getElementById('header-logo'));
}
