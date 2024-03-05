import React from 'react';
import { config } from './config';
import { NoSearchResultProps } from './types';
import NoSearchResultTemplate from './NoSearchResultTemplate';

const NoSearchResult = ({ page }: NoSearchResultProps): JSX.Element => {
  return <NoSearchResultTemplate page={page} config={config} />;
};

export default NoSearchResult;
