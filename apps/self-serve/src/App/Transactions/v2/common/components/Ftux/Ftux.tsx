import React from 'react';
import { config } from './config';
import { NoSearchResultProps } from 'apps/self-serve/src/App/Transactions/v2/common/components/NoSearchResult/types';
import NoSearchResultTemplate from 'apps/self-serve/src/App/Transactions/v2/common/components/NoSearchResult/NoSearchResultTemplate';

const Ftux = ({ page }: NoSearchResultProps): JSX.Element => {
  return <NoSearchResultTemplate page={page} config={config} />;
};

export default Ftux;
