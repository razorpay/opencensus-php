import Button from 'component/Button';

import { SelectField } from 'ui/Field';

import ListAdder from 'rzp/ui/AddToList';

export default class ReminderOptionSetting extends React.Component {
  state = {};

  onChange = id => e => {
    this.setState({
      [`${this.props.name}_${id}`]: e.target.value,
    });
  };

  renderRemovableSelect = (props = {}) => {
    return (
      <RemovableSelect
        {...props}
        onChange={this.onChange(props.id)}
        value={this.state[`${this.props.name}_${props.id}`]}
      />
    );
  };

  render() {
    const {
      name,
      isExpiry,
      onRemove,
      onChange,
      maxSelections,
      remindersList,
    } = this.props;

    const DEFAULT_DATA = {
      name,
      onRemove,
      onChange,
      options: remindersList,
    };

    return (
      <div class="setting">
        <label>
          {isExpiry ? 'For links with expiry' : 'For links without expiry'}
        </label>

        <ListAdder
          addButton={AddButton}
          maxItems={maxSelections}
          placeholderData={DEFAULT_DATA}
          item={this.renderRemovableSelect}
        />
      </div>
    );
  }
}

const AddButton = props => (
  <Button.Transparent {...props}> Add Reminder </Button.Transparent>
);

const RemovableSelect = ({
  id,
  name,
  options,
  onRemove,
  onChange,
  disabled,
  defaultValue,
}) => (
  <div class="removable-select">
    <SelectField
      required
      disabled={disabled}
      onChange={onChange}
      name={`${name}[${id}]`}
      defaultValue={defaultValue}
    >
      {options.map(({ id, value }) => (
        <option key={id} value={id}>
          {value}
        </option>
      ))}
    </SelectField>

    <i class="i-close" onClick={onRemove} />
  </div>
);
