import React, { Component } from 'react';
import Group, { GroupItem } from 'common/ui/Group';
import { PowerSelect } from 'react-power-select';

class FilterDropdown extends Component {
  constructor(props) {
    super(props);

    this.state = {
      selectedValues: {},
    };

    this.handleFilterChange = this.handleFilterChange.bind(this);
  }

  populateValues(filters, selectedFilters) {
    const { selectedValues } = this.state;

    filters.forEach(filter => {
      const filterName = filter.name,
        selectedOption = selectedFilters[filterName];

      filter.values.forEach(value => {
        if (value.options) {
          value.options.forEach(value => {
            value.filterName = filterName;

            if (selectedOption.value === value.value) {
              selectedValues[filterName] = value;
            }
          });
        } else {
          value.filterName = filterName;

          if (selectedOption.value === value.value) {
            selectedValues[filterName] = value;
          }
        }
      });
    });

    this.setState({ selectedValues });
  }

  handleFilterChange({ option: selectedItem }) {
    const { onFilterChange } = this.props;

    return onFilterChange && onFilterChange(selectedItem);
  }

  UNSAFE_componentWillMount() {
    this.populateValues(this.props.filters, this.props.selectedFilters);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    this.populateValues(nextProps.filters, nextProps.selectedFilters);
  }

  render() {
    const { filters, displayTextKey = 'text' } = this.props;

    const { selectedValues } = this.state;

    return (
      <div className="filtering-dropdown grouping-dropdown">
        <Group>
          <GroupItem>
            <i className="i i-sort" />
          </GroupItem>
          {filters.map((item, index) => {
            const selectedItem = selectedValues[item.name],
              powerSelectProps = {
                className: 'react-normal-select',
                options: item.values,
                optionLabelPath: displayTextKey,
                searchEnabled: true,
                selected: selectedItem,
                onChange: this.handleFilterChange,
              };

            if (item.disabled) {
              powerSelectProps.disabled = true;
            }

            return (
              <GroupItem className="dropdown-group-item" key={index}>
                <PowerSelect {...powerSelectProps} />
              </GroupItem>
            );
          })}
        </Group>
      </div>
    );
  }
}

export default FilterDropdown;
