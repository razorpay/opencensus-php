import React from 'react';
import { DehydratedState } from '@tanstack/react-query';

export const generateReactQueryScriptElement = (dehydratedState: DehydratedState): JSX.Element => {
  return (
    <script
      key="react-query"
      dangerouslySetInnerHTML={{
        __html: `window.__REACT_QUERY_STATE__ = ${JSON.stringify(dehydratedState)};`,
      }}
    />
  );
};
