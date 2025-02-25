import React from 'react';
import { SideNavSection } from '@razorpay/blade/components';
import { NavigationSection as NavigationSectionType } from '../types';
import { NavigationLink } from './NavigationLink';

const NavigationSection = (section: NavigationSectionType) => (
  <SideNavSection
    key={section.id}
    title={section.title}
    maxVisibleItems={section.one_nav_config?.initial_items_count}
  >
    {section.components.map((link) => (
      <NavigationLink {...link} key={link.id} />
    ))}
  </SideNavSection>
);

export { NavigationSection };
