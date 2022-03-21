import React from 'react';
import { findBy } from 'common/utils/rzp-utils';

import Button from 'common/new-ui/Button';

import { PowerSelect } from 'react-power-select';

import EntityDetailRow from 'merchant/components/EntityDetailRow';

const filterOptions = (options, selectedReminders, selectedOption) => {
  return options.filter((option) => {
    if (selectedOption && option.value === selectedOption.value) return true;

    return !findBy(selectedReminders, 'value', option.value);
  });
};

export default class ReminderOptionSetting extends React.Component {
  handleChange = (id) => ({ option }) => {
    const newList = [...this.props.selectedReminders];
    newList[id] = option;

    this.props.onChange(newList, this.props.name);
  };

  handleRemove = (option) => () => {
    const newList = this.props.selectedReminders.filter((ele) => ele.value != option.value);

    this.props.onChange(newList);
  };

  handleAddButton = () => {
    const { props } = this;

    const newList = [...props.selectedReminders];

    const nextOptions = filterOptions(props.remindersList, props.selectedReminders);

    newList.push(nextOptions[0]);

    this.props.onChange(newList);
  };

  render() {
    const { name, isExpiry, remindersList, selectedReminders, maxReminderCount } = this.props;

    const label = isExpiry ? 'For links with expiry' : 'For links without expiry';

    const showAddBtn =
      selectedReminders.length != remindersList.length &&
      selectedReminders.length < maxReminderCount;

    return (
      <div class="setting">
        <EntityDetailRow label={label}>
          <div class="add-to-list">
            {selectedReminders.map((selectedOption, idx) => {
              return (
                selectedOption && (
                  <RemovableSelect
                    required
                    key={idx}
                    options={remindersList}
                    selected={selectedOption}
                    onChange={this.handleChange(idx)}
                    highlightedOption={selectedOption}
                    name={`${name}_${selectedOption.value}`}
                    onRemove={this.handleRemove(selectedOption)}
                  />
                )
              );
            })}

            {showAddBtn && (
              <Button.Transparent onClick={this.handleAddButton}>
                <i class="i i-plus" /> Add Reminder
              </Button.Transparent>
            )}
          </div>
        </EntityDetailRow>
      </div>
    );
  }
}

const RemovableSelect = ({ options, onRemove, ...otherProps }) => (
  <div class="removable-select">
    <PowerSelect
      {...otherProps}
      options={options}
      showClear={false}
      searchEnabled={false}
      optionLabelPath="label"
      class="removable-power-select"
    />

    <span class="cross-wrapper" onClick={onRemove}>
      <i class="i-close" />
    </span>
  </div>
);
