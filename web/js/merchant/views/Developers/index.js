import React, { useEffect } from 'react';
import { Route, NavLink, Routes } from 'react-router-dom';
import Api from 'merchant/views/Developers/Api/index';
import Webhooks from 'merchant/views/Developers/Webhooks/List';
import ShowWhen from 'merchant/components/ShowWhen';
import { trackDeveloperConsoleOpened } from './events';

const Developers = ({ location: { pathname } }) => {
  useEffect(() => {
    trackDeveloperConsoleOpened();
  }, []);
  console.log(pathname);
  return (
    <tabbed-container>
      <header>
        <ShowWhen additionalCondition={(user) => user.isDeveloperConsoleEnabled}>
          <NavLink to="/developers/apis">API</NavLink>
        </ShowWhen>
        <ShowWhen additionalCondition={(user) => user.isDeveloperConsoleWebhooksTabEnabled}>
          <NavLink to="/developers/webhooks">Webhooks</NavLink>
        </ShowWhen>
      </header>
      <content style={{ background: 'none' }}>
        <Routes>
          <Route
            path="apis/*"
            element={
              <ShowWhen additionalCondition={(user) => user.isDeveloperConsoleEnabled}>
                <Api />
              </ShowWhen>
            }
          />
          <Route
            path="webhooks/*"
            element={
              <ShowWhen additionalCondition={(user) => user.isDeveloperConsoleWebhooksTabEnabled}>
                <Webhooks />
              </ShowWhen>
            }
          />
        </Routes>
      </content>
    </tabbed-container>
  );
};

export default Developers;
