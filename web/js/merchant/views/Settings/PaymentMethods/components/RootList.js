import React, { useState, useEffect, useRef } from 'react';
import ListItem from './ListItem';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';

const RootList = ({ instruments }) => {
  const ulRef = useRef(null);
  const [clickedName, setClickedName] = useState(null);

  const handleClickedInstument = (name) => {
    setClickedName(name);
    analyticsTrack({
      objectName: 'method',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'Payment Methods',
        method: name,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const updateShadow = () => {
    const ulElement = ulRef.current;
    if (!ulElement) return;

    const hasScroll = ulElement.scrollHeight > ulElement.clientHeight;

    const isAtTop = ulElement.scrollTop === 0;
    const isAtBottom = ulElement.scrollTop + ulElement.clientHeight === ulElement.scrollHeight;

    if (hasScroll) {
      if (isAtTop) {
        ulElement.style.boxShadow = 'inset 0px -20px 8px -10px #e3e3e780'; // down shadow
      } else if (isAtBottom) {
        ulElement.style.boxShadow = 'inset 0px 20px 8px -10px #e3e3e780'; // top shadow
      } else {
        ulElement.style.boxShadow =
          'inset 0px -20px 8px -10px #e3e3e780, inset 0px 20px 8px -10px #e3e3e780'; // both top & bottom shadow
      }
    } else {
      ulElement.style.boxShadow = 'none';
    }
  };

  useEffect(() => {
    const ulElement = ulRef.current;
    const scrollHandler = debounce(updateShadow, 100);
    if (ulElement) {
      ulElement.addEventListener('scroll', scrollHandler);
      /**
       * Determine initial shadow position
       * exceedsContainerHeight - to determine if list exceeds container height
       * isAtTop - to determine if scroll bar is at top
       * isAtBottom - to determine if scroll bar is at bottom
       */
      const exceedsContainerHeight = ulElement.scrollHeight > ulElement.clientHeight;
      if (exceedsContainerHeight) {
        const isAtTop = ulElement.scrollTop === 0;
        const isAtBottom = ulElement.scrollTop + ulElement.clientHeight === ulElement.scrollHeight;
        if (isAtTop) {
          ulElement.style.boxShadow = 'inset 0px -20px 8px -10px #e3e3e780';
        } else if (isAtBottom) {
          ulElement.style.boxShadow = 'inset 0px 20px 8px -10px #e3e3e780';
        }
      }
    }

    return () => {
      if (ulElement) {
        ulElement.removeEventListener('scroll', scrollHandler);
      }
    };
  }, [ulRef]);

  return (
    <div className="level-1">
      <ul ref={ulRef}>
        {instruments.map((instrument, index) => {
          return (
            <ListItem
              key={instrument?.name}
              index={index}
              instrument={instrument}
              from="root"
              handleClickedInstument={handleClickedInstument}
              clickedName={clickedName}
            />
          );
        })}
      </ul>
    </div>
  );
};

export default React.memo(RootList);
