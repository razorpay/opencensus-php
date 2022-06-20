import React from 'react';
import Time from 'common/ui/Time';

function Footer({ updatedAt }) {
  if (updatedAt) {
    return (
      <small>
        <i className="far fa-clock" />
        <span>
          Last updated on <Time value={Math.floor(updatedAt / 1000)} />
        </span>
      </small>
    );
  }
  return null;
}

export default Footer;
