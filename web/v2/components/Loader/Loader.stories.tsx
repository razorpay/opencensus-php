import React from 'react';
import Loader, { CenterLoader, FullPageLoader } from './index';

export default {
  title: 'Loader',
  component: Loader,
};

export const LoaderDefault: React.FC = () => <Loader />;
export const LoaderCenter: React.FC = () => <CenterLoader />;
export const LoaderFullPage: React.FC = () => <FullPageLoader />;
