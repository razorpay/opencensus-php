import React, { lazy, Suspense } from 'react';
import * as ReactDOM from 'react-dom';

const OneHome = lazy(() => import(/* webpackChunkName: "OneHome" */ '@apps/one-home/src/app'));

const App = () => {
  return (
    <Suspense fallback={<div>Loading One Home... Please Wait...</div>}>
      <OneHome />
    </Suspense>
  );
};

try {
  ReactDOM.render(<App />, document.getElementById('root'));
} catch (error: any) {
  console.log('error in loading the one-home module :', error);
}
