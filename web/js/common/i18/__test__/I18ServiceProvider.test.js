import React, { useContext } from 'react';
import { render } from 'test-utils';
import { I18ServiceProvider, I18ServiceContext } from 'common/i18/I18ServiceProvider';
import store from 'merchant/store';
import User from 'merchant/models/User';

const variantOn = { variables: { result: 'on' } };
const variantOff = { variables: { result: 'off' } };
const storeData = store.getState();

const defaultAbExperiments = {};

let mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const updateStore = (user) => {
  jest.spyOn(store, 'getState').mockImplementation(() => {
    const clonedStore = storeData;
    clonedStore.session.user = new User({
      ...clonedStore.session.user,
      ...user,
    });
    return clonedStore;
  });
};

const MyTestComponent = ({ callback }) => {
  const contextValue = useContext(I18ServiceContext);
  callback(contextValue);

  return <div>Test Complete</div>;
};

const getContextValue = () => {
  let contextValue = null;
  render(
    <I18ServiceContext.Provider value={{ isConfigTagEnabled: jest.fn() }}>
      <I18ServiceProvider store={store}>
        <MyTestComponent
          callback={(value) => {
            contextValue = value;
          }}
        />
      </I18ServiceProvider>
    </I18ServiceContext.Provider>,
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
    updateStore({
      configTags: {
        onboarding: {
          onboarding: true,
        },
      },
      tags: [],
    });
    mockAbExperiments = {
      config_based_tags: variantOn,
    };
    const contextValue = getContextValue();
    const isTagEnabled = contextValue.isConfigTagEnabled('onboarding.onboarding');
    expect(isTagEnabled).toBe(true);
  });

  it('fallback to default tags if variant is off', () => {
    updateStore({
      configTags: {},
      tags: ['i18_hide_onboarding'],
    });
    mockAbExperiments = {
      config_based_tags: variantOff,
    };
    const contextValue = getContextValue();
    const isTagEnabled = contextValue.isConfigTagEnabled('onboarding.onboarding');
    expect(isTagEnabled).toBe(true);
  });

  it('should not break if configTags is {}', () => {
    updateStore({
      configTags: {
        payment_buttons: {
          other_integration_methods: true,
          payment_buttons: true,
        },
      },
      tags: ['i18_hide_onboarding'],
    });
    mockAbExperiments = {
      config_based_tags: variantOn,
    };
    const contextValue = getContextValue();
    const isTagEnabled = contextValue.isConfigTagEnabled('onboarding.onboarding');
    expect(isTagEnabled).toBe(false);
  });
});
