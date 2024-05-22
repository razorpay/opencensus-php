import React, { Suspense } from 'react';
import * as ReactDOM from 'react-dom';
import { BrowserRouter } from 'react-router-dom';
import Wrapper from './Wrapper';

function PosApp(): React.ReactElement {
  return (
    <Suspense fallback={<div>Loading POS Routes</div>}>
      <BrowserRouter>
        <Wrapper />
      </BrowserRouter>
    </Suspense>
  );
}

try {
  ReactDOM.render(<PosApp />, document.getElementById('root'));
} catch (error: unknown) {
  console.log('error :', error);
}
