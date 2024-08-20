import React from 'react';
import { TabItem } from '@razorpay/blade/components';
import { useHref, useLinkClickHandler } from 'react-router-dom';

export function TabItemRouterLink({ onClick, replace = false, state, to, ...rest }) {
  const href = useHref(to);
  const handleClick = useLinkClickHandler(to, {
    replace,
    state,
  });

  return (
    <TabItem
      {...rest}
      href={href}
      onClick={(event) => {
        onClick?.(event);
        if (!event.defaultPrevented) {
          handleClick(event);
        }
      }}
    />
  );
}
