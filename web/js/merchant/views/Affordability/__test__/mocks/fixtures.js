import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';

const storeData = store.getState();

export const onboarding = {
  products: {
    affordability_widget: {
      feature: 'affordability_widget',
      isEnabled: true,
      isQuickGuideOpen: true,
      isTour: false,
      lastElementId: undefined,
      showOnboarding: true,
    },
  },
};

export const affordabilitySelfProps = {
  affordabilityWidget: {
    loading: false,
    affordability: {
      enabled: true,
      trialDate: new Date(),
    },
  },
  affordabilityWidgetProductOnBoarding: {
    feature: 'affordability_widget',
    isEnabled: true,
    isQuickGuideOpen: false,
    isTour: false,
    lastElementId: undefined,
    showOnboarding: true,
  },
};

export const AffordabilityStoreConfiguration = {
  isAffordabilityWidgetEnabled: jest.fn(() => true),
};

export const defaultProps = {
  closeModal: () => {},
  openModal: () => {},
};

export const orgAxisConfiguration = (value) => {
  return {
    affordabilityWidgetProductOnBoarding: {
      feature: 'affordability_widget',
      isEnabled: true,
      isQuickGuideOpen: false,
      isTour: false,
      lastElementId: undefined,
      showOnboarding: true,
    },
    user: {
      isOrgAxis: value,
    },
  };
};

export const rzpUserConfig = (activationStatus, role) => {
  return {
    activation_status: activationStatus,
    role,
  };
};

export const updateUser = (getStateSpy, user = {}) => {
  getStateSpy.mockImplementation(() => {
    const clonedStore = cloneDeep(storeData);
    clonedStore.session.user = {
      ...clonedStore.session.user,
      ...user,
    };
    return clonedStore;
  });
};
