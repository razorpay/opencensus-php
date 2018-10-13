import Input from 'component/Input';
import EditLayer from '../EditLayer';

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
    const fakeEle = window.document.querySelector('#fakeTextArea');

    fakeEle.innerHTML = content;
    this.elHeight = fakeEle.scrollHeight + 6 + 'px'; // 6 is the vertical padding(top+bottom) size of the textarea in css
    target.style.height = this.elHeight;
  };

  render() {
    const isEditable = this.state.isEditable;
    console.log('EL HEIGHT..', this.elHeight);

    return (
      <div id="description" class="text-wrap">
        {isEditable ? (
          <React.Fragment>
            <div id="fakeTextArea" />
            <Input.Textarea
              style={{ height: this.elHeight }}
              name="description"
              placeholder="Enter page description"
              info="Describe what the purpose of this page is and mention any additional details that might help the customer.\n\nNote:\nAll URLs will convert to links."
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
          <EditLayer onClick={this.toggleEditMode}>
            {this.props.description || (
              <span class="btn-link">+ Add page description</span>
            )}
          </EditLayer>
        )}
      </div>
    );
  }
}
