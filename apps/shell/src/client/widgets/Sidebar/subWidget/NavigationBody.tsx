import React, { Fragment } from 'react';
import { SideNavBody } from '@razorpay/blade/components';
import { NavigationBody as NavigationBodyProps } from '../types';
import { getSubWidget } from '../utils';

const NavigationBody = (body: NavigationBodyProps) => (
  <SideNavBody>
    {body.components.map((section) => (
      <Fragment key={section.id}>{getSubWidget(section)}</Fragment>
    ))}
  </SideNavBody>
);

export { NavigationBody };
