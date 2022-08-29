import React from 'react';
import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';
import GenericTooltip from 'common/ui/Tooltip';
import { graphIntervals } from '../constants';

const GraphIntervals = (props) => {
  const { selected, onChange, startDate, endDate } = props;

  return (
    <BtnGroup className="panel-action-item time-breakdown" value={selected} onChange={onChange}>
      {Object.values(graphIntervals).map((item, index) => {
        const isEnabled = item.isEnabled(startDate, endDate);
        const btnProps = {
          value: item.value,
          key: index,
          className: 'btn-default',
        };

        if (!isEnabled) btnProps.className = `${btnProps.className} disabled`;

        return (
          <Btn key={`${item}_${index}`} {...btnProps}>
            <span>{item.title}</span>
            {!isEnabled && <GenericTooltip align="bottom">{item.disabledText}</GenericTooltip>}
          </Btn>
        );
      })}
    </BtnGroup>
  );
};

export default GraphIntervals;
