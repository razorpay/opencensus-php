import React from 'react';

import { NavigationLink } from './subWidget/NavigationLink';
import { NavigationSection } from './subWidget/NavigationSection';
import { NavigationFooter } from './subWidget/NavigationFooter';
import { NavigationBody } from './subWidget/NavigationBody';
import { NavigationModeToggle } from './subWidget/NavigationModeToggle';

export const subWidgetKeyToComponentMapping = {
  navigation_link: (props): JSX.Element => <NavigationLink {...props} />,
  navigation_section: (props): JSX.Element => <NavigationSection {...props} />,
  navigation_footer: (props): JSX.Element => <NavigationFooter {...props} />,
  navigation_body: (props): JSX.Element => <NavigationBody {...props} />,
  mode_toggle: (props): JSX.Element => <NavigationModeToggle {...props} />,
};
