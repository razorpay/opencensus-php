import { classList } from 'common/utils/rzp-utils';
import { Link } from 'react-router-dom';
import React from 'react';

export default ({ to, icon, title, description, className }) => (
  <Link className={classList('LinkCard', className)} to={to}>
    {icon && <i className={classList('i', icon, 'icon-card')} />}
    <i className="i i-chevron-right" />
    <div>
      <span>{title}</span>
      <p>{description}</p>
    </div>
  </Link>
);
