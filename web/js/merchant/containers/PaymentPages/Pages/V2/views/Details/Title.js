import Input from 'component/Input';
import EditLayer from '../EditLayer';

export default class extends React.PureComponent {
  state = { isEditable: !this.props.title };
  allowAutoFocus = !this.props.title;

  toggleEditMode = e => {
    const toEdit = !this.state.isEditable;
    this.setState({ isEditable: toEdit });

    if (!this.allowAutoFocus) {
      setTimeout(function() {
        if (toEdit) {
          const titleEle = document.querySelector(
            '#details-section input[name="title"]'
          );
          titleEle && titleEle.focus();
        }
      });
    }
  };

  render() {
    const isEditable = this.state.isEditable || !this.props.title;

    return (
      <div class="title title--big">
        {isEditable ? (
          <Input
            name="title"
            placeholder="Enter page title here"
            info="This is the heading of your page. Help your customers recognise the page with this"
            defaultValue={this.props.title}
            onBlur={e => {
              this.toggleEditMode();
              this.props.updateData(e);
            }}
            autoFocus={this.allowAutoFocus}
          />
        ) : (
          <div onClick={this.toggleEditMode}>{this.props.title}</div>
        )}

        <div class="title-underline" />
      </div>
    );
  }
}
