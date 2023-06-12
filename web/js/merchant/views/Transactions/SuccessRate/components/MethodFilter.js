import React from 'react';

import GroupingDropdown from 'merchant/containers/Home/GroupingDropdown';
import CardTypes from './CardTypes';
import { checkIfFilterValid } from 'merchant/views/Transactions/SuccessRate/helper';

const MethodFilter = (props) => {
  const {
    filtersList,
    handleGroupingChange,
    disabled,
    selectedGrouping,
    isInternationalEnabled,
    isOptimizerEnabled,
    activeTab,
  } = props;
  const renderGroupingDropdown = (groupingData = [], index) => {
    const filteredGroupingData = groupingData.filter(({ value }) =>
      checkIfFilterValid({ activeTab, filter: value, flags: { isInternationalEnabled } }),
    );

    if (filteredGroupingData?.length) {
      return (
        <GroupingDropdown
          key={index}
          className={`sr-tab__filter ${disabled ? ' PowerSelect--disabled' : ''}`}
          onGroupChange={handleGroupingChange}
          grouping={filteredGroupingData}
          selectedGrouping={selectedGrouping?.[index] || filteredGroupingData?.[0]}
        />
      );
    }
    return null;
  };

  if (filtersList?.length) {
    return (
      <div className="sr-filter sr-method-filters flex">
        {!isOptimizerEnabled && (
          <div>
            <label>Card type:</label>
            <div className="panel-actions">
              <CardTypes />
            </div>
          </div>
        )}
        <div>
          <label>Filter:</label>
          <div className="flex">{filtersList?.map(renderGroupingDropdown)}</div>
        </div>
      </div>
    );
  }
  return null;
};

export default MethodFilter;
