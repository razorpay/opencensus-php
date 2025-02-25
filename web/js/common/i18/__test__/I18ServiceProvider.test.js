import React, { useContext } from 'react';
import { I18ServiceContext, I18ServiceProvider } from '@federated/dashboards/payments/services/i18Service';

import User from 'merchant/models/User';
import { render } from 'test-utils';

const variantOn = { variables: { result: 'on' } };
const variantOff = { variables: { result: 'off' } };

const defaultAbExperiments = {};

let mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const MyTestComponent = ({ callback }) => {
  const contextValue = useContext(I18ServiceContext);
  callback(contextValue);
  return <div>Test Complete</div>;
};

const getContextValue = (initialState) => {
  let contextValue = null;
  render(
    <I18ServiceContext.Provider value={{ isConfigTagEnabled: jest.fn() }}>
      <I18ServiceProvider>
        <MyTestComponent
          callback={(value) => {
            contextValue = value;
          }}
        />
      </I18ServiceProvider>
    </I18ServiceContext.Provider>,
    { initialState },
  );
  return contextValue;
};

describe('I18ServiceProvider', () => {
  beforeEach(() => {
    mockAbExperiments = defaultAbExperiments;
  });

  it('should provide isConfigTagEnabled function in the context', () => {
    const contextValue = getContextValue();
    expect(contextValue).toHaveProperty('isConfigTagEnabled');
    expect(typeof contextValue.isConfigTagEnabled).toBe('function');
  });

  it('use config tags if variant is enabled', () => {
    mockAbExperiments = {
      config_based_tags: variantOn,
    };
    const contextValue = getContextValue({
      session: {
        user: new User({
          configTags: {
            onboarding: {
              onboarding: true,
            },
          },
          tags: [],
        }),
      },
    });
    const isTagEnabled = contextValue.isConfigTagEnabled('onboarding.onboarding');
    expect(isTagEnabled).toBe(true);
  });

  it('fallback to default tags if variant is off', () => {
    mockAbExperiments = {
      config_based_tags: variantOff,
    };
    const contextValue = getContextValue({
      session: {
        user: new User({
          configTags: {},
          tags: ['i18_hide_onboarding'],
        }),
      },
    });
    const isTagEnabled = contextValue.isConfigTagEnabled('onboarding.onboarding');
    expect(isTagEnabled).toBe(true);
  });

  it('should not break if configTags is {}', () => {
    mockAbExperiments = {
      config_based_tags: variantOn,
    };
    const contextValue = getContextValue({
      session: {
        user: new User({
          configTags: {
            payment_buttons: {
              other_integration_methods: true,
              payment_buttons: true,
            },
          },
          tags: ['i18_hide_onboarding'],
        }),
      },
    });
    const isTagEnabled = contextValue.isConfigTagEnabled('onboarding.onboarding');
    expect(isTagEnabled).toBe(false);
  });
});
