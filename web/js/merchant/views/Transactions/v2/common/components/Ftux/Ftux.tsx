import React from 'react';
import { config } from './config';
import { NoSearchResultProps } from 'merchant/views/Transactions/v2/common/components/NoSearchResult/types';
import NoSearchResultTemplate from 'merchant/views/Transactions/v2/common/components/NoSearchResult/NoSearchResultTemplate';

const Ftux = ({ page }: NoSearchResultProps): JSX.Element => {
  return <NoSearchResultTemplate page={page} config={config} />;
};

export default Ftux;
