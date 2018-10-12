import Input from 'component/Input';
import EditLayer from '../EditLayer';

export default class extends React.PureComponent {
  state = { isEditable: false };

  toggleEditMode = e => {
    this.setState({ isEditable: !this.state.isEditable });
  };

  render() {
    const isEditable = this.state.isEditable;

    return (
      <div id="description" class="text-wrap">
        {isEditable ? (
          <Input.Textarea
            name="description"
            placeholder="Enter page description"
            info={
              'Describe what the purpose of this page is and mention any additional details that might help the customer.\n\nNote:\nAll URLs will convert to links.'
            }
            defaultValue={this.props.description}
            onBlur={e => {
              this.toggleEditMode();
              this.props.updateData(e);
            }}
            autoFocus
          />
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
