import { PRODUCT_ICON_MAP } from '@apps/shell/src/client/components/Navigation/TopNavigation/constants';
import { PRODUCT_PATH_MAP_FOR_INTERNAL_NAVIGATION } from '@apps/shell/src/client/components/Navigation/constants';
import { DASHBOARD_PRIORITY_RANKS } from '@libs/shared-types';
import errorService from '@razorpay/universe-cli/errorService';
import { DASHBOARD_TEAMS } from '@libs/shared-types';

export const getNavigateActionValue = (actionDetails: any) => {
  switch (true) {
    case 'url' in actionDetails: {
      return {
        type: 'external_navigation',
        navigateTo: actionDetails?.url,
      };
    }
    case 'path' in actionDetails: {
      return {
        type: 'internal_navigation',
        navigateTo: actionDetails?.path,
      };
    }
    default: {
    }
  }
};

export const getActionValue = (productAction: any, alias) => {
  try {
    const actionType = productAction?.type;
    const actions = productAction?.actions;
    const actionAlias = productAction?.alias;

    switch (actionType) {
      case 'navigate': {
        return getNavigateActionValue(actions?.[0]?.action_params);
      }
      case 'access_denied_page':
      case 'growth_page':
        return getNavigateActionValue(getActionParamForGrowthAndAccessDeniedPage(alias));
      case 'modal':
        return actionAlias;
      default:
        throw new Error(`Invalid action type received: ${actionType}`);
    }
  } catch (error) {
    errorService.captureError(error, {
      tags: {
        team: DASHBOARD_TEAMS.CROSS_SELL_EXPERIENCE,
        module: '[@shell]: TopNavigation - getActionValue',
      },
      rank: DASHBOARD_PRIORITY_RANKS.P0,
    });
    console.error('Error processing action value:', error);
    return null;
  }
};

// Helper function to get action params for Growth and Access Denied pages
// UCS does not send action params, so we derive the navigation path based on the alias.
const getActionParamForGrowthAndAccessDeniedPage = (alias) => {
  return { path: PRODUCT_PATH_MAP_FOR_INTERNAL_NAVIGATION[alias] };
};

const getPageData = (component) => {
  if (component?.type === 'growth_page' || component?.type === 'access_denied_page') {
    return {
      title: component?.title,
      description: component?.description,
      imageSrc: component?.background_img,
      actions: component?.actions,
    };
  }
  return null;
};

export const getProducts = ({ data }: { data: any }) => {
  return data?.components?.reduce((acc, component) => {
    if (component.type === 'more_navigation_item') {
      const moreItems = component.components.map((subComponent) => ({
        id: subComponent.id,
        alias: subComponent.alias,
        title: subComponent.title,
        description: subComponent.description,
        icon: PRODUCT_ICON_MAP[subComponent.data?.navigation_data?.icon],
        isAlwaysOverflowing: true,
        selectAction: {
          actionType: subComponent.components?.[0]?.type,
          value: getActionValue(subComponent.components?.[0], component.alias),
          pageData: getPageData(component.components?.[0]),
        },
      }));
      return [...acc, ...moreItems];
    } else {
      const trailingIcon =
        PRODUCT_ICON_MAP[
          component.components?.[0]?.actions?.find((action) => action.type === 'navigate')?.icon
        ];
      acc.push({
        id: component.id,
        alias: component.alias,
        title: component.title,
        description: component.description,
        icon: PRODUCT_ICON_MAP[component.data?.navigation_data?.icon],
        trailingIcon,
        selectAction: {
          actionType: component.components?.[0]?.type,
          value: getActionValue(component.components?.[0], component.alias),
          pageData: getPageData(component.components?.[0]),
        },
      });
      return acc;
    }
  }, []);
};
