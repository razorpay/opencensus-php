import { affordabilityFeaturesMapping } from 'merchant/views/Affordability/AffordabilityWidget/Onboarding/data';
import {
  generatePayload,
  getAffFeatureFlag,
} from 'merchant/views/Affordability/AffordabilityWidget/Onboarding/helper';

describe('Validate: generatePayload', () => {
  beforeAll(() => {
    window.rzp_user = {};
  });

  test('Should enable both flags if both are disabled', () => {
    window.rzp_user = {
      aff_features: [
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET,
          value: false,
        },
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET,
          value: false,
        },
      ],
    };

    const expected = {
      features: { affordability_widget: true, affordability_widget_set: true },
      should_sync: 0,
    };

    expect(JSON.stringify(generatePayload())).toBe(JSON.stringify(expected));
  });

  test('Should enable only one flag whichever is disabled', () => {
    window.rzp_user = {
      aff_features: [
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET,
          value: false,
        },
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET,
          value: true,
        },
      ],
    };

    let expected = {
      features: { affordability_widget: true },
      should_sync: 0,
    };

    expect(JSON.stringify(generatePayload())).toBe(JSON.stringify(expected));

    window.rzp_user = {
      aff_features: [
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET,
          value: true,
        },
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET,
          value: false,
        },
      ],
    };

    expected = {
      features: { affordability_widget_set: true },
      should_sync: 0,
    };

    expect(JSON.stringify(generatePayload())).toBe(JSON.stringify(expected));
  });
});

describe('Validate: getAffFeatureFlag', () => {
  test('Should return matching affordability flag', () => {
    window.rzp_user = {
      aff_features: [
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET,
          value: false,
        },
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET,
          value: true,
        },
      ],
    };

    let expected = {
      feature: 'affordability_widget_set',
      value: true,
    };

    expect(
      JSON.stringify(getAffFeatureFlag(affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET)),
    ).toBe(JSON.stringify(expected));

    expected = {
      feature: 'affordability_widget',
      value: false,
    };

    expect(
      JSON.stringify(getAffFeatureFlag(affordabilityFeaturesMapping.AFFORDABILITY_WIDGET)),
    ).toBe(JSON.stringify(expected));

    window.rzp_user = {
      aff_features: [],
    };
    expect(
      JSON.stringify(getAffFeatureFlag(affordabilityFeaturesMapping.AFFORDABILITY_WIDGET)),
    ).not.toBe(JSON.stringify(expected));
  });
});
