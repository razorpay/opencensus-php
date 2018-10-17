import Input from 'component/Input';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import Button from 'component/Button';

export default class extends React.PureComponent {
  state = { isEditable: false };

  toggleEditMode = e => {
    this.setState({ isEditable: !this.state.isEditable });
  };

  handleOnInput = ({ target }) => {
    const content = target.value;
    const fakeEle = window.document.querySelector(
      '#terms-details .fakeTextArea'
    );

    fakeEle.innerHTML = content;
    this.elHeight = fakeEle.scrollHeight + 6 + 'px'; // 6 is the vertical padding(top+bottom) size of the textarea in css
    target.style.height = this.elHeight;
  };

  render() {
    const isEditable = this.state.isEditable;

    return (
      <div id="terms-details" class="text-wrap">
        {isEditable ? (
          <React.Fragment>
            <div class="fakeTextArea" />
            <label>Terms & Conditions:</label>
            <Input.Textarea
              style={{ height: this.elHeight }}
              name="terms"
              placeholder="Enter Terms & Conditions"
              defaultValue={this.props.terms}
              onInput={this.handleOnInput}
              onBlur={e => {
                this.toggleEditMode();
                this.props.updateData(e);
              }}
              autoFocus
            />
          </React.Fragment>
        ) : this.props.terms ? (
          <React.Fragment>
            <label>Terms & Conditions:</label>
            <div>{this.props.terms}</div>
          </React.Fragment>
        ) : (
          <span class="help-content">
            <Button.Transparent class="btn-link" onClick={this.toggleEditMode}>
              + Add Terms & Conditions
            </Button.Transparent>
            <Popover align="right" theme="dark">
              <PopoverBody>Add Terms & Conditions if any</PopoverBody>
            </Popover>
          </span>
        )}
      </div>
    );
  }
}
