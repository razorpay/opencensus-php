import { classList } from '@libs/shared-utils';
import React from 'react';

export default (props) => (
  <span {...props} className={classList(props.className, 'PlaceholderLoader')} />
);
