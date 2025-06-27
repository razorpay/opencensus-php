import { SIDEEBAR_PRODUCTS_TITLES } from '@dashboards/payments/components/SidebarV2/constants/constants';
import { ExperimentInfoType } from '@libs/web-nexus/common/splitz/types';

type SectionDetailsFromSplitz = {
  product_id: string;
  section_name: string;
  tooltip_content: string;
};

export const getRecommendedProducts = (sectionDetailsFromSplitz: SectionDetailsFromSplitz) => {
  try {
    if (!SIDEEBAR_PRODUCTS_TITLES[sectionDetailsFromSplitz?.product_id]) {
      return [];
    }
    const recommendedProducts = [
      {
        title: SIDEEBAR_PRODUCTS_TITLES[sectionDetailsFromSplitz?.product_id],
        product_id: sectionDetailsFromSplitz?.product_id,
        tooltip_content: sectionDetailsFromSplitz?.tooltip_content || '',
      },
    ];
    return recommendedProducts;
  } catch (error) {
    return [];
  }
};

export const getRecommendedProductsSection = (abExperiments: ExperimentInfoType) => {
  try {
    const sectionDetailsFromSplitz = abExperiments?.['sidebar_recommended_products']
      ?.variables as SectionDetailsFromSplitz;

    return {
      section_name: sectionDetailsFromSplitz?.['section_name'] || 'RECOMMENDED FOR YOU',
      section_id: 'recommended_products',
      product_options: getRecommendedProducts(sectionDetailsFromSplitz),
    };
  } catch (error) {
    return {};
  }
};
