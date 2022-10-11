import React from 'react';

import GroupingDropdown from 'merchant/containers/Home/GroupingDropdown';
import CardTypes from './CardTypes';
import { connect } from 'react-redux';

const MethodFilter = (props) => {
  const { filtersList, handleGroupingChange, disabled, selectedGrouping, user = {} } = props;
  const { isOptimizerEnabled = false } = user;

  const renderGroupingDropdown = (groupingData = [], index) => {
    if (groupingData?.length > 0) {
      return (
        <GroupingDropdown
          key={index}
          className={`sr-tab__filter ${disabled ? ' PowerSelect--disabled' : ''}`}
          onGroupChange={handleGroupingChange}
          grouping={groupingData}
          selectedGrouping={selectedGrouping?.[index] || groupingData?.[0]}
        />
      );
    }
    return null;
  };

  if (filtersList?.length > 0) {
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

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps)(MethodFilter);
