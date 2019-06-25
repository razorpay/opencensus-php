import Button from 'component/Button';

import { SelectField } from 'ui/Field';

import AddToList from 'rzp/ui/AddToList';

export default class ReminderOptionSetting extends React.Component {
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

        <AddToList
          AddButton={AddButton}
          maxLength={maxSelections}
          defaultData={DEFAULT_DATA}
          ChildComponent={RemovableSelect}
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
