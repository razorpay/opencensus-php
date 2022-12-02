import React, { Component } from 'react';

import { getFormattedAmountNew, getFormattedNumber } from 'common/utils/rzp-utils';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
import GroupingDropdown from 'merchant/components/Home/GroupingDropdown';
import StackedBars from 'merchant/containers/Home/StackedBars';

class MobileTraffic extends Component {
  render() {
    const {
      isLoading,
      hasNoData,
      aggTypes,
      onAggChange,
      selectedAgg,
      error,
      data,
      isCurrency,
      user,
    } = this.props;

    return (
      <GenericPanel
        className="traffic-container"
        isLoading={isLoading}
        hasNoData={hasNoData}
        error={error}
      >
        <PanelTopbar className="clearfix">
          <GroupingDropdown
            displayTextKey="title"
            grouping={aggTypes}
            onGroupChange={onAggChange}
            selectedGrouping={selectedAgg}
          />
        </PanelTopbar>
        <PanelBody>
          {!isLoading && !!data && (
            <StackedBars
              data={data}
              textKey={'label'}
              formatValue={(isCurrency && getFormattedAmountNew) || getFormattedNumber}
              user={user}
            />
          )}
        </PanelBody>
      </GenericPanel>
    );
  }
}

export default MobileTraffic;
