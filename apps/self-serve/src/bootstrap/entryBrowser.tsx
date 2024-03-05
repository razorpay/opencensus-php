import React, { Suspense } from 'react';
import * as ReactDOM from 'react-dom';
import { BrowserRouter } from 'react-router-dom';

import SelfServeRouter from './Route/SelfServeRouter';

function App(): JSX.Element {
  return (
    <Suspense fallback={<div>Loading Self Serve Route</div>}>
      <BrowserRouter>
        <SelfServeRouter />
      </BrowserRouter>
    </Suspense>
  );
}

try {
  ReactDOM.render(<App />, document.getElementById('root'));
} catch (error: unknown) {
  console.log('error :', error);
}
