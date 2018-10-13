import EditLayer from '../EditLayer';

export default class extends React.PureComponent {
  render() {
    const hasSocialShare = this.props.hasSocialShare;

    const Icons = (
      <React.Fragment>
        <img src="/img/social-media/fb.png" alt="Facebook share" />
        <img src="/img/social-media/twitter.png" alt="Twitter share" />
        <img src="/img/social-media/whatsapp.png" alt="Whatsapp share" />
      </React.Fragment>
    );

    return (
      <div id="share-details">
        <EditLayer>
          <input
            name="social_share"
            type="checkbox"
            hidden
            onClick={this.props.updateData}
          />
          {hasSocialShare ? (
            <React.Fragment>
              <label>Share this on:</label>
              <div class="share-icons">{Icons}</div>
            </React.Fragment>
          ) : (
            <React.Fragment>
              <span class="btn-link">+ Add social media share icons</span>
              <div class="share-icons icons--grayscale">{Icons}</div>
            </React.Fragment>
          )}
        </EditLayer>
      </div>
    );
  }
}
