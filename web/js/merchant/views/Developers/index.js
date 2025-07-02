import React, { useEffect } from 'react';
import { Route, NavLink, Routes } from 'react-router-dom';
import Api from 'merchant/views/Developers/Api/index';
import Webhooks from 'merchant/views/Developers/Webhooks/List';
import ShowWhen from 'merchant/components/ShowWhen';
import { trackDeveloperConsoleOpened } from './events';
import { useI18Service } from '@libs/web-nexus/common/i18';
import { RouteGuard } from 'merchant_common/components/RouteGuard';

const Developers = ({ location: { pathname } }) => {
  const { isConfigTagEnabled } = useI18Service();

  useEffect(() => {
    trackDeveloperConsoleOpened();
  }, []);

  return (
    <tabbed-container>
      <header>
        <ShowWhen
          additionalCondition={(user) =>
            user.isDeveloperConsoleEnabled && !isConfigTagEnabled('developers.api_section')
          }
        >
          <NavLink to="/developers/apis">API</NavLink>
        </ShowWhen>
        <ShowWhen
          additionalCondition={(user) =>
            user.isDeveloperConsoleWebhooksTabEnabled &&
            !isConfigTagEnabled('developers.webhooks_section')
          }
        >
          <NavLink to="/developers/webhooks">Webhooks</NavLink>
        </ShowWhen>
      </header>
      <content style={{ background: 'none' }}>
        <Routes>
          <Route
            path="apis/*"
            element={
              <RouteGuard
                additionalCondition={(user) => user.isDeveloperConsoleEnabled && !isConfigTagEnabled('developers.api_section')}
                defaultPath="/developers/webhooks"
              >
                <Api />
              </RouteGuard>
            }
          />
          <Route
            path="webhooks/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isDeveloperConsoleWebhooksTabEnabled &&
                  !isConfigTagEnabled('developers.webhooks_section')
                }
              >
                <Webhooks />
              </RouteGuard>
            }
          />
        </Routes>
      </content>
    </tabbed-container>
  );
};

export default Developers;
