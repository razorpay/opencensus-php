import React, { useEffect } from 'react';
import { Route, NavLink } from 'react-router-dom';
import Api from 'merchant/views/Developers/Api/index';
import { trackDeveloperConsoleOpened } from './Api/events';

const Developers = () => {
  useEffect(() => {
    trackDeveloperConsoleOpened();
  }, []);

  return (
    <tabbed-container>
      <header id="myaccount-header">
        <NavLink to="/developers/apis">API</NavLink>
      </header>
      <content style={{ background: 'none' }}>
        <Route path="/developers/apis" component={Api} />
      </content>
    </tabbed-container>
  );
};

export default Developers;
