import React from 'react';
import { SidebarNavigation } from './Sidebar';

export const widgetKeyToComponentMapping = {
  navigation: (props) => <SidebarNavigation {...props} />,
};
