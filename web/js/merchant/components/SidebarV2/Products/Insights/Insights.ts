import { CHECKOUT_TABS, getDashboardTabs, INSIGHTS_DASHBOARDS, SUCCESS_RATE_TABS } from 'merchant/views/Insights/constants';
import {SparklesIcon,LayoutIcon} from '@razorpay/blade/components';
import { isExperimentEnabled } from 'common/splitz/utils';

const buildL2ProductItems = (
  tabs: Array<{ name: string; link: string }>,
  dashboardLink: string,
  experimentFlag: string
) => {
  return tabs.map(tab => ({
    title: tab.name,
    href: `insights/${dashboardLink}/${tab.link}`,
    icon: SparklesIcon,
    experimentFlag,
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) => 
      abExperiments && isExperimentEnabled(abExperiments[experimentFlag])
  }));
};

const INSIGHTS_SUCCESS_RATE_L2_PRODUCTS_DATA = buildL2ProductItems(
    SUCCESS_RATE_TABS,
    INSIGHTS_DASHBOARDS[0].link,
    'insights_success_rate_experiment'
  );
  
  const INSIGHTS_CHECKOUT_L2_PRODUCTS_DATA = buildL2ProductItems(
    CHECKOUT_TABS,
    INSIGHTS_DASHBOARDS[1].link,
    'insights_checkout_experiment'
  );

const getInsightsL1Products = (items: any[], user: any, abExperiments: any) => {
    const filteredL1Products = items.filter(product => {
      const conditionResult = product.additionalCondition(user, { abExperiments, isConfigTagEnabled: () => false });
      return conditionResult;
    });
    return filteredL1Products;
  };


const INSIGHTS_L1_PRODUCTS_DATA = [
    {
      title: INSIGHTS_DASHBOARDS[0].name,
      href : INSIGHTS_SUCCESS_RATE_L2_PRODUCTS_DATA[0].href,
      icon: LayoutIcon,
      experimentFlag: 'insights_success_rate_experiment',
      additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>{
       return  abExperiments && isExperimentEnabled(abExperiments.insights_success_rate_experiment)
      },
      items: INSIGHTS_SUCCESS_RATE_L2_PRODUCTS_DATA
    },
    {
      title: INSIGHTS_DASHBOARDS[1].name,
      href : INSIGHTS_CHECKOUT_L2_PRODUCTS_DATA[0].href,
      icon: LayoutIcon,
      experimentFlag: 'insights_checkout_experiment',
      additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
        abExperiments && isExperimentEnabled(abExperiments.insights_checkout_experiment),
      items: INSIGHTS_CHECKOUT_L2_PRODUCTS_DATA
    }
  ];

  export { buildL2ProductItems, getInsightsL1Products, INSIGHTS_L1_PRODUCTS_DATA, INSIGHTS_SUCCESS_RATE_L2_PRODUCTS_DATA, INSIGHTS_CHECKOUT_L2_PRODUCTS_DATA };