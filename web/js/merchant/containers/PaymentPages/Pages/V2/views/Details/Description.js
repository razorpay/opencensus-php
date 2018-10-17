import Input from 'component/Input';
import Button from 'component/Button';

const DESC_LIMIT = {
  DESKTOP: 720,
  MOBILE: 125,
};

export default class extends React.PureComponent {
  state = { isEditable: false };

  toggleEditMode = e => {
    this.setState({ isEditable: !this.state.isEditable });
  };

  handleOnInput = ({ target }) => {
    const content = target.value;
    const fakeEle = window.document.querySelector('#description .fakeTextArea');

    fakeEle.innerHTML = content;
    this.elHeight = fakeEle.scrollHeight + 6 + 'px'; // 6 is the vertical padding(top+bottom) size of the textarea in css
    target.style.height = this.elHeight;
  };

  render() {
    const isEditable = this.state.isEditable;

    return (
      <div id="description" class="text-wrap">
        {isEditable ? (
          <React.Fragment>
            <div class="fakeTextArea" />
            <Input.Textarea
              style={{ height: this.elHeight }}
              name="description"
              placeholder="Enter page description"
              info={`Describe what the purpose of this page is and mention any additional details that might help the customer.

Note:
All URLs will convert to links.`}
              defaultValue={this.props.description}
              onInput={this.handleOnInput}
              onBlur={e => {
                this.toggleEditMode();
                this.props.updateData(e);
              }}
              autoFocus
            />
          </React.Fragment>
        ) : (
          this.props.description || (
            <Button.Transparent class="btn-link" onClick={this.toggleEditMode}>
              + Add page description
            </Button.Transparent>
          )
        )}
      </div>
    );
  }
}
