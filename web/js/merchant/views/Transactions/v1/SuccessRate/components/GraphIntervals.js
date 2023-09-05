import React from 'react';
import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';
import GenericTooltip from 'common/ui/Tooltip';
import { graphIntervals } from 'merchant/views/Transactions/v1/SuccessRate/constants';

const GraphIntervals = (props) => {
  const { selected, onChange, startDate, endDate } = props;

  return (
    <BtnGroup className="panel-action-item" value={selected} onChange={onChange}>
      {Object.values(graphIntervals).map((item, index) => {
        const isEnabled = item.isEnabled(startDate, endDate);
        const btnProps = {
          key: index,
          className: 'btn-default',
          value: item.value,
        };

        if (!isEnabled) btnProps.disabled = 'disabled';

        return (
          <Btn key={`${item.value}_${index}`} {...btnProps}>
            <span>{item.title}</span>
            {!isEnabled && <GenericTooltip align="top">{item.disabledText}</GenericTooltip>}
          </Btn>
        );
      })}
    </BtnGroup>
  );
};

export default GraphIntervals;
