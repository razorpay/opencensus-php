import React from 'react';
import { findBy } from 'common/utils/rzp-utils';

import Button from 'common/new-ui/Button';

import { PowerSelect } from 'react-power-select';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

const filterOptions = (options, selectedReminders, selectedOption) => {
  return options.filter((option) => {
    if (selectedOption && option.value === selectedOption.value) return true;

    return !findBy(selectedReminders, 'value', option.value);
  });
};

export default class ReminderOptionSetting extends React.Component {
  handleChange =
    (id) =>
    ({ option }) => {
      const newList = [...this.props.selectedReminders];
      newList[id] = option;
      selfServeTrackInitiate({
        selfServeAction: 'PL Reminder Created',
        page: 'Reminders',
        screen: 'Settings',
      });
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

    const label = isExpiry ? 'For links with an expiry date' : 'For links without an expiry date';

    const showAddBtn =
      selectedReminders.length != remindersList.length &&
      selectedReminders.length < maxReminderCount;

    return (
      <div className="setting">
        <EntityDetailRow label={label}>
          <div className="add-to-list">
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
                <i className="i i-plus" /> Add Reminder
              </Button.Transparent>
            )}
          </div>
        </EntityDetailRow>
      </div>
    );
  }
}

const RemovableSelect = ({ options, onRemove, ...otherProps }) => (
  <div className="removable-select">
    <PowerSelect
      {...otherProps}
      options={options}
      showClear={false}
      searchEnabled={false}
      optionLabelPath="label"
      className="removable-power-select"
    />

    <span className="cross-wrapper" onClick={onRemove}>
      <i className="i-close" />
    </span>
  </div>
);
