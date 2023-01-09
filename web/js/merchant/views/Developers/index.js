import React, { useEffect } from 'react';
import { Route, NavLink } from 'react-router-dom';
import Api from 'merchant/views/Developers/Api/index';
import Webhooks from 'merchant/views/Developers/Webhooks/List';
import ShowWhen from 'merchant/components/ShowWhen';
import { trackDeveloperConsoleOpened } from './events';

const Developers = () => {
  useEffect(() => {
    trackDeveloperConsoleOpened();
  }, []);
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
        <ShowWhen additionalCondition={(user) => user.isDeveloperConsoleEnabled}>
          <Route path="/developers/apis" component={Api} />
        </ShowWhen>
        <ShowWhen additionalCondition={(user) => user.isDeveloperConsoleWebhooksTabEnabled}>
          <Route path="/developers/webhooks" component={Webhooks} />
        </ShowWhen>
      </content>
    </tabbed-container>
  );
};

export default Developers;
