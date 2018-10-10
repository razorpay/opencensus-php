import React from 'react';

import InstantActivationsCard from './Instant';
import RegularActivationsCard from './Regular';

export default ({ showInstantActivation, ...props }) => {
  return showInstantActivation ? (
    <InstantActivationsCard {...props} />
  ) : (
    <RegularActivationsCard {...props} />
  );
};
