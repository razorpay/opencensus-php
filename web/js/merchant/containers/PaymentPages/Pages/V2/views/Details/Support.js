import Input from 'component/Input';
import EditLayer from '../EditLayer';

const phoneIcon = (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M0 0h24v24H0z" fill="none" />
    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
  </svg>
);

const emailIcon = (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
    <path d="M0 0h24v24H0z" fill="none" />
  </svg>
);

export default class extends React.PureComponent {
  state = { isEditable: false };

  toggleEditMode = e => {
    this.setState({ isEditable: !this.state.isEditable });
  };

  render() {
    const { support } = this.props,
      hasSupportInfo = support && (support.email || support.phone),
      isEditable = this.state.isEditable;

    let content = '';

    if (isEditable) {
      content = (
        <React.Fragment>
          <label>Contact Us:</label>
          <div class="sub-detail">
            {emailIcon}
            <Input
              name="support[email]"
              placeholder="Enter support email"
              defaultValue={support && support.email}
              onBlur={e => {
                this.props.updateData(e);
              }}
              autoFocus
            />
          </div>
          <div class="sub-detail">
            {phoneIcon}
            <Input
              name="support[phone]"
              placeholder="Enter support phone"
              defaultValue={support && support.phone}
              onBlur={e => {
                this.props.updateData(e);
              }}
            />
          </div>
        </React.Fragment>
      );
    } else {
      content = (
        <EditLayer
          onClick={this.toggleEditMode}
          infoTxt="Provide your contact information so your customers can reach out"
        >
          <span class="btn-link">+ Add your contact information</span>
          <div class="share-icons icons--grayscale">
            {emailIcon}
            {phoneIcon}
          </div>
        </EditLayer>
      );
    }

    return <div id="support-details">{content}</div>;
  }
}
