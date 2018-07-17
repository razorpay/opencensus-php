import React, { Component } from 'react';

import { getFormattedAmountNew, getFormattedNumber } from 'rzp/utils/rzp-utils';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchantLA/components/Home/GenericPanel';
import GroupingDropdown from 'merchantLA/components/Home/GroupingDropdown';
import StackedBars from 'merchantLA/containers/Home/StackedBars';

class MobileTraffic extends Component {
  constructor(props) {
    super(props);
  }

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
          {!isLoading &&
            !!data && (
              <StackedBars
                data={data}
                textKey={'label'}
                formatValue={
                  (isCurrency && getFormattedAmountNew) || getFormattedNumber
                }
              />
            )}
        </PanelBody>
      </GenericPanel>
    );
  }
}

export default MobileTraffic;
