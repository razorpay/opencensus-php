import React from 'react';
import { NavLink } from 'react-router-dom';
import WhiteLabelTheme from './WhiteLabelTheme';

export const Configuration = (): JSX.Element => {
  return (
    <div className="tabbed-container">
      <header>
        <NavLink exact to="/partners/config">
          Configuration
        </NavLink>
        <NavLink exact to="/partners/settings">
          Settings
        </NavLink>
      </header>
      <div className="content">
        <div className="content-wrapper " id="partner-configurator-content">
          <WhiteLabelTheme />
        </div>
      </div>
    </div>
  );
};
