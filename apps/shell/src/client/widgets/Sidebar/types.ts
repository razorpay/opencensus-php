import { BadgeProps } from '@razorpay/blade/components';

interface NavigationModeToggle {
  id: string;
  type: 'mode_toggle';
  one_nav_config: {
    initial_value: 'test' | 'live';
    is_disabled: boolean;
  };
}

export interface NavigationFooter {
  id: string;
  type: 'navigation_footer';
  components: Array<NavigationModeToggle | NavigationLink>;
}

type TitleSuffix = { type: 'badge'; value: string; color: BadgeProps['color'] };

export interface NavigationLink {
  id: string;
  title: string;
  type: 'navigation_link';
  actions?: { action: string };
  icon: string;
  one_nav_config?: {
    default?: boolean;
    tooltip?: string;
    title_suffix?: TitleSuffix;
  };
  tooltip?: string;
  components?: Array<NavigationLink>;
}

export interface NavigationSection {
  id: string;
  type: 'navigation_section';
  title: string;
  one_nav_config?: {
    initial_items_count: number;
  };
  icon: string;
  components: Array<NavigationLink>;
}

export interface NavigationBody {
  id: string;
  type: 'navigation_body';
  components: Array<NavigationSection | NavigationLink>;
}

export interface Navigation {
  id: string;
  type: 'navigation';
  // only present for payments for now
  one_nav_config?: {
    banner?: {
      activation_progress: number;
      activation_status: string;
      nc_eligible: boolean;
    };
  };
  components: Array<NavigationBody | NavigationFooter>;
}

export interface OneNavigationTab {
  id: string;
  type: 'tab';
  title: string;
  one_nav_config?: {
    default?: boolean;
  };
  components: Array<Navigation>;
}

export interface IOneNavigationResponse {
  id: string;
  one_nav_config: {
    brand_image: string;
    brand_colour: string;
  };
  components: Array<OneNavigationTab>;
}
