import Input from 'component/Input';
import EditLayer from '../EditLayer';

export default props => (
  <div id="description-details">
    <Title title={props.title} onChange={props.onChange} />
    <PaymentFor description={props.description} onChange={props.onChange} />
  </div>
);

class Title extends React.PureComponent {
  // state = { isEditable: !this.props.title };
  state = { isEditable: !this.props.title };

  toggleEditMode = () => {
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
            value={this.props.title}
            onChange={this.props.onChange}
            onBlur={this.toggleEditMode}
            autoFocus
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

class PaymentFor extends React.PureComponent {
  state = {
    isEditable: false,
  };

  render() {
    return (
      <div id="description" class="text-wrap">
        {!this.state.isEditable ? (
          <Input.Textarea
            name="description"
            info="Example: Acme Infotech Private Limited"
          />
        ) : (
          this.props.description
        )}
      </div>
    );
  }
}
