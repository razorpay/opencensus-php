import { useState } from 'react';

import { useSplitzService } from 'common/splitz';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { isRecurringInstrumentEnabled } from 'merchant/views/AccountAndSettings/PaymentMethods/utils';
import { INSTRUMENT_SLUGS } from 'merchant/views/Settings/PaymentMethods/constants';

import ListItem from './ListItem';

const IntermediateList = ({ instrument }) => {
  const [clickedName, setClickedName] = useState(null);
  const splitz = useSplitzService();

  const handleClickedInstument = (name) => {
    setClickedName(name);
    analyticsTrack({
      objectName: name,
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'Payment Methods',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };
  return (
    <div className="level-2">
      <ul>
        {instrument.intermediateList.map((intermediateItem, index) => {
          if (
            !isRecurringInstrumentEnabled(splitz) &&
            intermediateItem.slug === INSTRUMENT_SLUGS.RECURRING
          )
            return null;
          return (
            <ListItem
              key={intermediateItem.name}
              index={index}
              instrument={intermediateItem}
              from="intermediate"
              handleClickedInstument={handleClickedInstument}
              clickedName={clickedName}
            />
          );
        })}
      </ul>
    </div>
  );
};
export default IntermediateList;
