/**
 * title
 * isActive
 * href
 * target
 * icon
 * trailing
 * onClick
 * description
 * isAlwaysOverflowing
 */
import {
  AcceptPaymentsIcon,
  RazorpayXIcon,
  ShoppingBagIcon,
  RazorpayxPayrollIcon,
  BillIcon,
  UsersIcon,
  BankIcon,
  AwardIcon,
  ArrowUpRightIcon,
  CompanyRegistrationIcon,
} from '@razorpay/blade/components';
import { Component, ListItem } from './typings/component';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import type { TabNavItemProps } from '@razorpay/blade/components';

export type Item = TabNavItemProps & {
  description?: string;
  isAlwaysOverflowing?: boolean | null;
};

export type ProductType = Item & ListItem;

export type ExtendedListItems = ProductType[];

export const constructListItems = (
  data: Component[],
  isAlwaysOverflowingParent: boolean | null = null,
): ExtendedListItems => {
  let listItems: ExtendedListItems = [];

  data.forEach((item) => {
    const isAlwaysOverflowing = isAlwaysOverflowingParent;

    if ((item.type === 'more_navigation_item' && item.components?.length) ?? 0 > 0) {
      const nestedItems = constructListItems(item.components ?? [], true);
      listItems = listItems.concat(nestedItems);
      return listItems;
    }

    const icon = productIconsMap[item.data?.navigation_data?.icon || ''];

    // Add the current item to the list
    listItems.push({
      id: item.id,
      isActive: item.data?.navigation_data?.default_item || false,
      icon,
      // @ts-ignore
      trailing:
        item.components?.[0]?.actions?.find((action) => action.type === 'navigate')?.icon || '', // Update to assign an empty string as default value
      title: item.title || '',
      description: item.description || '',
      isAlwaysOverflowing,
      datum: item, // This is the entire Component object
    });
  });

  return listItems;
};

export const productIconsMap = {
  AcceptPaymentsIcon,
  RazorpayXIcon,
  ShoppingBagIcon,
  RazorpayxPayrollIcon,
  BillIcon,
  UsersIcon,
  BankIcon,
  AwardIcon,
  ArrowUpRightIcon,
  CompanyRegistrationIcon,
};

export const determineNavigationType = (components: Component[]) => {
  if (!components || components.length === 0) {
    return { type: 'error_page', value: '' }; // No components to process
  }

  for (const component of components) {
    if (component.type === 'navigate') {
      // Check if the component has actions and action_params
      const action = component.actions?.find((action) => action.type === 'navigate');
      if (action && action.action_params) {
        if (action.action_params.path) {
          // Internal redirect
          return { type: 'internal', value: action.action_params.path };
        } else if (action.action_params.url) {
          // External redirect
          return { type: 'external', value: action.action_params.url };
        }
      }
    } else if (component.type === 'modal') {
      // Modal action
      return { type: 'modal', value: component.alias };
    } else if (component.type === 'growth_page') {
      return { type: 'growth_page', value: component.alias };
    } else if (component.type === 'access_denied_page') {
      return { type: 'access_denied_page', value: component.alias };
    }
  }

  // If no matching type is found
  return { type: 'none', value: '' };
};

interface ConnectedNavigationEnabled {
  user: User;
  abExperiments: any;
}

export const isConnectedNavigationEnabled = ({
  user,
  abExperiments,
}: ConnectedNavigationEnabled): boolean => {
  const isActivated = user?.isAccepted;
  const { isPosSalesAgent, isPosEkycAgent } = checkIfPosSalesAgent({ user, abExperiments });

  return (
    isActivated &&
    user.isCountryIndia &&
    user.isOrgRZP &&
    !isPosSalesAgent &&
    !isPosEkycAgent &&
    isExperimentEnabled(abExperiments.connected_navigation)
  );
};