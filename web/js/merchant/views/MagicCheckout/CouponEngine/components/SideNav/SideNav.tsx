import React from 'react';

// ui import
import NavItem from 'merchant/views/MagicCheckout/OrderStatusUpload/components/NavItem';

// types import
import { NavItemsMapping } from 'merchant/views/MagicCheckout/CouponEngine/components/SideNav/types';

interface SideNavProps {
  tabs: NavItemsMapping[];
  onTabClick: (id: string) => void;
  activeNav: string;
}

const SideNav: React.FC<SideNavProps> = ({ tabs, onTabClick, activeNav }) => {
  return (
    <div className="nav-sidebar col-sm-2 no-padding">
      {tabs.map((tab) => {
        const isActive = tab.id === activeNav;
        return (
          <NavItem
            title={tab.title}
            key={tab.id}
            onTabClick={() => onTabClick(tab.id)}
            active={isActive}
          />
        );
      })}
    </div>
  );
};

export default SideNav;
