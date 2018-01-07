import React from 'react';

import Change from './Change';

const round = value => Math.round(value * 100) / 100;

export default ({ previous, current }) => {
  const change = current - previous;
  return (
    <Change value={change}>
      {` (${round(Math.abs(change) / previous * 100)})%`}
    </Change>
  );
};
