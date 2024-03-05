import React from 'react';

// import { useStore } from 'shell/commonStore';

import Wrapper from '../Wrapper/Wrapper';
import SelfServeTransactionV2Landing from 'apps/self-serve/src/App/Transactions/v2/Landing';
// import { RouterConfig, useRouteLocalBuild } from './routeUtils';

function SelfServeRouter(): JSX.Element {
  // const store = useStore();

  // const __selfServeRoutes = useRouteLocalBuild({
  //   routes: selfServeRoutes,
  //   path: '/selfServe',
  // });

  // console.log('zustand shared store:', { store });

  return (
    <Wrapper>
      {/* <RouterConfig routes={selfServeRoutes} /> */}
      <SelfServeTransactionV2Landing />
    </Wrapper>
  );
}

export default SelfServeRouter;
