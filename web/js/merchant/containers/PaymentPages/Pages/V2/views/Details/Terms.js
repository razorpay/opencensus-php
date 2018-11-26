import Input from 'component/Input';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import Button from 'component/Button';

const infoText = 'Add Terms & Conditions';

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
    const fakeEle = window.document.querySelector(
      '#terms-details .fake-textarea'
    );

    let newLineChars = 0;
    for (let i = 0; i < content.length; i++) {
      if (content[i] === '\n') {
        newLineChars++;
      }
    }

    let fakeLinesHeight = newLineChars * 22; // 22 is line-height

    fakeEle.innerHTML = content;
    this.elHeight = fakeEle.scrollHeight + fakeLinesHeight + 10 + 'px'; // 10 is combination of vertical padding and line height of the textarea in css
    target.style.height = this.elHeight;
  }

  componentDidMount() {
    this.autoAdjustHeight(
      document.body.querySelector('#terms-details textarea[name="terms"]')
    );
  }

  render() {
    const isEditable = this.state.isEditable || this.props.terms;

    return (
      <div id="terms-details">
        {isEditable ? (
          <React.Fragment>
            <div class="fake-textarea" />
            <label>Terms & Conditions:</label>
            <Input.Textarea
              name="terms"
              placeholder="Enter Terms & Conditions"
              defaultValue={this.props.terms}
              info={infoText}
              onInput={this.handleOnInput}
              onBlur={e => {
                this.setState({ isEditable: false });
                this.props.updateData(e);
              }}
              validator={function(val) {
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
          <span class="help-content">
            <Button.Transparent
              class="btn-link"
              onClick={() => {
                this.setState({ isEditable: true });
              }}
            >
              + Add Terms & Conditions
            </Button.Transparent>
            <Popover align="right" theme="dark">
              <PopoverBody>{infoText}</PopoverBody>
            </Popover>
          </span>
        )}
      </div>
    );
  }
}
