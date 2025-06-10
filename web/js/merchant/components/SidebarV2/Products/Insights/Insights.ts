import { CHECKOUT_TABS, getDashboardTabs, INSIGHTS_DASHBOARDS, SUCCESS_RATE_TABS, FALLBACK_INSIGHTS_DATA } from 'merchant/views/Insights/constants';
import { SparklesIcon, LayoutIcon } from '@razorpay/blade/components';
import { getFallbackInsightsOnError } from 'merchant/views/Insights/constants';
import { 
  InsightMetric, 
  InsightsApiResponse, 
  InsightsL2Product, 
  InsightsL1ProductsData 
} from 'merchant/components/SidebarV2/Products/Insights/types';

const buildL2ProductItems = (
  tabs: Array<{ name: string; link: string }>,
  dashboardLink: string,
  metricsData: InsightMetric[] = []
): InsightsL2Product[] => {
  try {
    const availableTabs = tabs.filter(tab => {
      if (dashboardLink === 'success-rate' && tab.link === 'overview' && metricsData.length > 0) {
        return true;
      }
      
      if (!metricsData.length) return true;
      if (tab.link === 'magic' && metricsData.some(metric => 
        Object.keys(metric)[0].toLowerCase() === 'magicx')) {
        return true;
      }
      
      return metricsData.some(metric => 
        Object.keys(metric)[0].toLowerCase() === tab.link.toLowerCase()
      );
    });

    const l2Products = availableTabs.map(tab => {
      let metricValue: InsightMetric | undefined;
      if (tab.link === 'magic') {
        metricValue = metricsData.find(metric => 
          Object.keys(metric)[0].toLowerCase() === 'magicx' || 
          Object.keys(metric)[0].toLowerCase() === 'magic'
        );
      } else {
        metricValue = metricsData.find(metric => 
          Object.keys(metric)[0].toLowerCase() === tab.link.toLowerCase()
        );
      }
      
      return {
        title: tab.name,
        href: `insights/${dashboardLink}/${tab.link}`,
        icon: SparklesIcon,
        metricCount: metricValue ? Object.values(metricValue)[0] : null
      };
    });

    return l2Products.sort((a, b) => {
      const aValue = a.metricCount ? parseFloat(a.metricCount) : -1;
      const bValue = b.metricCount ? parseFloat(b.metricCount) : -1;
      
      if (isNaN(aValue) && isNaN(bValue)) return 0;
      if (isNaN(aValue)) return 1;
      if (isNaN(bValue)) return -1;
      
      return bValue - aValue;
    });
  } catch (error) {
    console.error('Error building L2 product items:', error);
    return [];
  }
};

const getDashboardConfig = (dashboardType: string) => {
  try {
    const dashboard = INSIGHTS_DASHBOARDS.find(dashboard => dashboard.link === dashboardType);
    if (!dashboard) return null;
    
    let tabs;
    
    switch (dashboardType) {
      case 'success-rate':
        tabs = SUCCESS_RATE_TABS;
        break;
      case 'checkout':
        tabs = CHECKOUT_TABS.filter(tab => tab.link !== 'magicx');
        break;
      default:
        return null;
    }
    
    return { dashboard, tabs };
  } catch (error) {
    console.error('Error getting dashboard config:', error);
    return null;
  }
};


const buildInsightsProducts = (insightsData: InsightsApiResponse): InsightsL1ProductsData => {
  try {
    const result: InsightsL1ProductsData = [];
    
    Object.keys(insightsData).forEach(dashboardType => {
      try {
        const metrics = insightsData[dashboardType];
        
        if (!metrics || metrics.length === 0) return;
        
        const config = getDashboardConfig(dashboardType);
        if (!config) return;
        
        const { dashboard, tabs } = config;
        
        const l2Products = buildL2ProductItems(
          tabs,
          dashboard.link,
          metrics
        );
        
        if (l2Products.length === 0) return;
        
        result.push({
          title: dashboard.name,
          href: l2Products.length > 0 ? l2Products[0].href : `insights/${dashboard.link}`,
          icon: LayoutIcon,
          items: l2Products
        });
      } catch (error) {
        console.error(`Error processing dashboard type ${dashboardType}:`, error);
      }
    });

    return result.length > 0 ? result : getFallbackInsightsOnError();
  } catch (error) {
    console.error('Error building insights products:', error);
    return getFallbackInsightsOnError();
  }
};

const getInsightsL1ProductsFromFallbackData  = () => {
  try {
    return buildInsightsProducts(FALLBACK_INSIGHTS_DATA);
  } catch (error) {
    console.error('Error getting insights from fallback data:', error);
    return getFallbackInsightsOnError();
  }
};

const getInsightsL1ProductsFromData = (insightsData: InsightsApiResponse): InsightsL1ProductsData => {
  try {
    return buildInsightsProducts(insightsData);
  } catch (error) {
    console.error('Error getting insights from data:', error);
    return getFallbackInsightsOnError();
  }
};

export { 
  getInsightsL1ProductsFromFallbackData,
  getInsightsL1ProductsFromData,
};