import debounce from 'rzp/utils/debounce';

export default class AddToList extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      list: props.defaultData || [],
    };

    this.handleAddButton = debounce(this._handleAddButton.bind(this), 300);
  }

  _handleAddButton() {
    const newList = [...this.state.list];
    newList.push(this.props.placeholderData);

    this.setState(
      {
        list: newList,
      },
      () => {
        this.props.onUpdate && this.props.onUpdate(this.state.list);
      }
    );
  }

  handleRemove = index => () => {
    this.setState({
      list: this.state.list.splice(index, 1),
    });
  };

  render() {
    const {
      addButton: AddButton,
      item: ChildComponent,
      maxItems,
      className,
    } = this.props;

    const showAddBtn = maxItems && this.state.list.length < maxItems;

    return (
      <div class={`add-to-list ${className}`}>
        {this.state.list.map((props, idx) => (
          <ChildComponent
            id={idx}
            key={idx}
            {...props}
            onRemove={this.handleRemove(idx)}
          />
        ))}

        {showAddBtn && <AddButton onClick={this.handleAddButton} />}
      </div>
    );
  }
}
