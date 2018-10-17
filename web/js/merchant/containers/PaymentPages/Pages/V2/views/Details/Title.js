import Input from 'component/Input';
import EditLayer from '../EditLayer';

export default class extends React.PureComponent {
  state = { isEditable: !this.props.title };

  toggleEditMode = e => {
    this.setState({ isEditable: !this.state.isEditable });
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
          />
        ) : (
          <EditLayer onClick={this.toggleEditMode}>
            {this.props.title}
          </EditLayer>
        )}

        <div class="title-underline" />
      </div>
    );
  }
}
