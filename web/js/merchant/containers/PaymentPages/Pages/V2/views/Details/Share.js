import Popover, { PopoverBody } from 'rzp/ui/Popover';
import Button from 'component/Button';
import RemoveBtn from '../RemoveBtn';

export default class extends React.PureComponent {
  onUpdate = allowSocialShare => {
    this.props.updateData({
      target: {
        name: 'allow_social_share',
        value: allowSocialShare,
      },
    });
  };

  render() {
    const allowSocialShare = this.props.allowSocialShare;

    const Icons = (
      <React.Fragment>
        <span class="facebook" />
        <span class="twitter" />
        <span class="whatsapp" />
      </React.Fragment>
    );

    return (
      <div id="share-details">
        {allowSocialShare ? (
          <React.Fragment>
            <label>Share this on:</label>
            <div class="share-icons">
              {Icons}
              <RemoveBtn onClick={() => this.onUpdate(false)} />
            </div>
          </React.Fragment>
        ) : (
          <span class="help-content">
            <Button.Transparent
              class="btn-link"
              onClick={() => this.onUpdate(true)}
            >
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
