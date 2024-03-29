import React, { memo } from 'react';
import { ActionList as ActionL } from '@razorpay/blade/components';
import { ActionListComponentProps } from './types';

const ActionListComponent = <T,>({
  options,
  itemComponent,
}: ActionListComponentProps<T>): JSX.Element => {
  return (
    <ActionL>
      {options.map((data, index) => {
        return itemComponent({ data, index });
      })}
    </ActionL>
  );
};

// Mapping all the ActionListItem directly impacts the performance of main component where its actually used.
// Reason being the option map loop run for every render.
export const ActionList = memo(ActionListComponent) as typeof ActionListComponent;
