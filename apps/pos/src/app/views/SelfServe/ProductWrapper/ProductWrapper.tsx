import { NavLink, useLocation } from 'react-router-dom';
import React from 'react';

import { isMobileResolution as isMobile, classList } from '@libs/shared-utils';

type ProductWrapperProps = {
  children: React.ReactNode;
  extra: React.ReactNode;
  isMobile: boolean;
  tabsData: {
    title: string;
    url: string;
    isMatchStartsWith: boolean;
    onTabClick?: () => void;
    hidden?: boolean;
    isActive?: (match: any, location: any) => boolean;
  }[];
  customHeaderRightClass?: string;
};

const ProductWrapper = ({
  children,
  extra,
  tabsData,
  customHeaderRightClass,
}: ProductWrapperProps) => {
  const headerRightClass = classList(
    'header-right',
    isMobile() ? 'mobile' : '',
    customHeaderRightClass || '',
  );
  const location = useLocation();
  return (
    <div className="tabbed-container updated">
      <header
        id="link-header"
        style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}
      >
        <div className="header-left">
          {tabsData.map(
            (tab) =>
              !tab.hidden && (
                <NavLink
                  end={!tab.isMatchStartsWith}
                  to={tab.url}
                  key={tab.title}
                  className={(navLink) =>
                    navLink.isPending
                      ? 'pending'
                      : navLink.isActive || tab.isActive?.(null, location)
                      ? 'active'
                      : ''
                  }
                  onClick={() => {
                    if (tab.onTabClick) tab.onTabClick();
                  }}
                >
                  {tab.title}
                </NavLink>
              ),
          )}
        </div>
        <div className={headerRightClass}>{extra}</div>
      </header>
      {children}
    </div>
  );
};

export default ProductWrapper;
