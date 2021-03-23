import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default class extends React.PureComponent {
  state = { isEditable: false };

  handleOnInput = ({ target }) => {
    this.autoAdjustHeight(target);
  };

  autoAdjustHeight(target) {
    if (!target) {
      return;
    }

    const content = target.value;
    const fakeEle = window.document.querySelector('#terms-details .fake-textarea');

    fakeEle.value = content;
    const newHeight = fakeEle.scrollHeight;

    target.style.height = newHeight + 'px';
  }

  componentDidMount() {
    this.autoAdjustHeight(document.body.querySelector('#terms-details textarea[name="terms"]'));
  }

  render() {
    const isEditable = this.state.isEditable || this.props.terms;

    return (
      <div id="terms-details">
        {isEditable ? (
          <React.Fragment>
            <textarea class="fake-textarea" readOnly />
            <label>Terms & Conditions:</label>
            <Input.Textarea
              name="terms"
              placeholder="Enter Terms & Conditions"
              defaultValue={this.props.terms}
              onInput={this.handleOnInput}
              onBlur={(e) => {
                this.setState({ isEditable: false });
                this.props.updateData(e);
              }}
              validator={function (val) {
                if (!val) {
                  return;
                } else if (val.length < 5) {
                  return 'Value should be minimum 5 characters';
                }
              }}
              minLength="5"
              autoFocus
            />
          </React.Fragment>
        ) : (
          <Button.Transparent
            class="btn-link"
            onClick={() => {
              analyticsTrack({
                objectName: 'terms',
                actionName: 'added',
                screen: 'create payment page',
                properties: {
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              this.setState({ isEditable: true });
            }}
          >
            + Add Terms & Conditions
          </Button.Transparent>
        )}
      </div>
    );
  }
}
