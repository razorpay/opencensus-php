import React from 'react';

// types import
import { NavItemProps } from 'merchant/views/MagicCheckout/CouponEngine/components/SideNav/types';

const NavItem: React.FC<NavItemProps> = ({ title, onTabClick, active }) => {
  return (
    <div className={`nav-item${active ? ' active' : ''}`} onClick={onTabClick}>
      {title}
    </div>
  );
};

export default NavItem;
