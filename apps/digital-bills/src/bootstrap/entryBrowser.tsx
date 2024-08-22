import React, { Suspense } from 'react';
import * as ReactDOM from 'react-dom';
import { BrowserRouter } from 'react-router-dom';
import Wrapper from './Wrapper';

function DigitalBillsWrapper(): React.ReactElement {
  return (
    <Suspense fallback={<div>Loading Digital Bills Route</div>}>
      <BrowserRouter>
        <Wrapper />
      </BrowserRouter>
    </Suspense>
  );
}

try {
  ReactDOM.render(<DigitalBillsWrapper />, document.getElementById('root'));
} catch (error: unknown) {
  console.log('error :', error);
}
