import React from 'react';
import Time from 'common/ui/Time';

function Footer({ updatedAt }) {
  if (updatedAt) {
    return (
      <p>
        <i className="i i-clock" />
        <span>
          {' '}
          Last updated on{' '}
          <Time format="DD-MMM, YYYY, hh:mm a" value={Math.floor(updatedAt / 1000)} />
        </span>
      </p>
    );
  }
  return null;
}

export default Footer;
