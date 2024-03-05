import React from 'react';
import { classList } from '@dashboard/shared-utils/rzp-utils';

const PlaceholderLoader = (props) => (
  <span {...props} className={classList(props.className, 'PlaceholderLoader')} />
);

export default PlaceholderLoader;
