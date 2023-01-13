import React from 'react';
import { NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import WhiteLabelTheme from './WhiteLabelTheme';

const Configuration = (): JSX.Element => {
  return (
    <div className="tabbed-container">
      <header>
        <NavLink exact to="/partners/settings">
          Settings
        </NavLink>
        <ShowWhen additionalCondition={(user) => user.isPartnershipForPhantomEnabled}>
          <NavLink exact to="/partners/config">
            Configuration
          </NavLink>
        </ShowWhen>
      </header>
      <div className="content">
        <div className="content-wrapper " id="partner-configurator-content">
          <WhiteLabelTheme />
        </div>
      </div>
    </div>
  );
};

export default Configuration;
