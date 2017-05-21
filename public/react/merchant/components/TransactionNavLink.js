import { Component, PropTypes } from 'react';
import { NavLink } from 'react-router-dom';

const TransactionNavLink = ({ onClick, children, ...otherProps }) => {
  return (
    <NavLink
      class="NavLink__transaction"
      {...otherProps}
      onClick={event => {
        if (!event.metaKey) {
          event.preventDefault();
          onClick();
        }
      }}
    >
      <code>{children}</code>
    </NavLink>
  );
};

TransactionNavLink.defaultProps = {
  onClick: () => {},
};

export default TransactionNavLink;
