import Popover, { PopoverBody } from 'rzp/ui/Popover';
import Button from 'component/Button';

export default class extends React.PureComponent {
  onUpdate = () => {
    this.props.updateData({
      target: {
        name: 'social_share',
        value: true,
      },
    });
  };

  render() {
    const hasSocialShare = this.props.hasSocialShare;

    const Icons = (
      <React.Fragment>
        <span class="facebook" />
        <span class="twitter" />
        <span class="whatsapp" />
      </React.Fragment>
    );

    return (
      <div id="share-details">
        {hasSocialShare ? (
          <React.Fragment>
            <label>Share this on:</label>
            <div class="share-icons">{Icons}</div>
          </React.Fragment>
        ) : (
          <span class="help-content">
            <Button.Transparent class="btn-link" onClick={this.onUpdate}>
              + Add social media share icons
            </Button.Transparent>
            <Popover align="right" theme="dark">
              <PopoverBody>
                This lets your customers share this page on their social media
              </PopoverBody>
            </Popover>
          </span>
        )}
      </div>
    );
  }
}
