import { useState } from 'react';
import ListItem from './ListItem';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const IntermediateList = ({ instrument }) => {
  const [clickedName, setClickedName] = useState(null);
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
    <div class="level-2">
      <ul>
        {instrument.intermediateList.map((intermediateItem, index) => {
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
