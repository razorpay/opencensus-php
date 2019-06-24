import debounce from 'rzp/utils/debounce';

export default class AddToList extends React.Component {
  static defaultProps = {
    maxLength: null,
    ChildComponent: {},
    AddButton: {},
  };

  constructor(props) {
    super(props);

    this.state = {
      list: [],
    };

    this.handleAddButton = debounce(this.handleAddButton.bind(this), 300);
  }

  handleAddButton(data) {
    this.setState(
      {
        list: [...this.state.list, { ...this.props.defaultData }],
      },
      () => {
        if (this.props.onAdd) {
          this.props.onAdd(data);
        }
      }
    );
  }

  render() {
    const { AddButton, ChildComponent, maxLength, className } = this.props;

    const showAddBtn = maxLength && this.state.list.length < maxLength;

    return (
      <div class={`add-to-list ${className}`}>
        {this.state.list.map((props, idx) => (
          <ChildComponent id={idx} {...props} />
        ))}

        {showAddBtn && <AddButton onClick={this.handleAddButton} />}
      </div>
    );
  }
}
