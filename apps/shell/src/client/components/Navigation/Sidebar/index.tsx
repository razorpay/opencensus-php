import React from 'react';
import { SidebarNavigation } from '@apps/shell/src/client/widgets/Sidebar';
import { IOneNavigationResponse } from '@apps/shell/src/client/widgets/Sidebar/types';

export const Sidebar = ({ data }: { data: IOneNavigationResponse }) => (
  <SidebarNavigation navigation={data} />
);

