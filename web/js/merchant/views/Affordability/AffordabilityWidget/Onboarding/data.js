import React from "react";

export const PlatformsTitle = 'Choose your website platform';

export const PlatformsList = [
  {
    id: 'shopify',
    icon: require('assets/affordability_widget/shopify.svg'),
    title: 'Shopify',
    desc: `Run offers across all payment methods or for specific banks, card networks and wallets.`,
  },
  {
    id: 'woocommerce',
    icon: require('assets/affordability_widget/wooc.svg'),
    title: 'WooCommerce',
    // eslint-disable-next-line prettier/prettier
    desc:
      'Give your customers flat discounts or percentage discounts depending on your promotional scheme.',
  },
  {
    id: 'others',
    icon: require('assets/affordability_widget/others.svg'),
    title: 'Others',
    desc: 'Run No Cost EMI offers for your customers across any bank.',
  },
];

export const FeatureTilesList = [
  {
    icon: require('assets/affordability_widget/aff-cart.svg'),
    metric: '47%',
    featureDesc: <>Increase in average order value</>,
    description: 'Higher average order value with affordable payment options and offers',
  },
  {
    icon: require('assets/affordability_widget/aff-growth.svg'),
    metric: '57%',
    featureDesc: <>Growth in customer conversion</>,
    description: 'Increase in conversion rates by helping customers make informed choices',
  },
  {
    icon: require(`assets/affordability_widget/aff-customers.svg`),
    metric: '38%',
    featureDesc: <>Higher customer retention</>,
    description: 'Increase in customer satisfaction with more offers and discounts to choose from',
  },
];

export const platformTitleMapping = {
  woocommerce: 'WooCommerce',
  shopify: 'Shopify',
};

export const platformIdMapping = {
  woocommerce: 'woocommerce',
  shopify: 'shopify',
};

export const affordabilityFeaturesMapping = {
  AFFORDABILITY_WIDGET: 'affordability_widget',
  AFFORDABILITY_WIDGET_SET: 'affordability_widget_set',
};

export const AffordabilityFeaturesFlag = [
  affordabilityFeaturesMapping.AFFORDABILITY_WIDGET,
  affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET,
];
