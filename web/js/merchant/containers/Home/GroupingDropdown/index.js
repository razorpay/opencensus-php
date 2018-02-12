import React, { Component } from 'react';

import GroupingDropdown from 'merchant/components/Home/GroupingDropdown';

import { trackGroupingChange } from './ga';

const defaultDisplayTextKey = 'text';

class GroupingDropdownContainer extends Component {
  constructor(props) {
    super(props);
    this.handleGroupChange = this.handleGroupChange.bind(this);
  }

  handleGroupChange(arg) {
    const { option: selectedGrouping } = arg,
      { sectionTitle, onGroupChange, displayTextKey } = this.props;

    trackGroupingChange(
      selectedGrouping[displayTextKey || defaultDisplayTextKey],
      sectionTitle
    );

    return onGroupChange && onGroupChange(arg);
  }

  render() {
    const {
      sectionTitle,
      onGroupChange,
      displayTextKey = defaultDisplayTextKey,
      ...props
    } = this.props;

    return (
      <GroupingDropdown
        {...props}
        onGroupChange={this.handleGroupChange}
        displayTextKey={displayTextKey}
      />
    );
  }
}

export default GroupingDropdownContainer;
