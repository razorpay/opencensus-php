import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

const versioningExperimentName = 'ProductVersioningV1';
export const trackProductVersioningWidgetLoaded = ({ productListVisible, numberOfProducts, cardSetType, screen = 'home page' }) => {
  analyticsTrackWithUserInfo({
    objectName: 'Product Versioning Widget',
    actionName: 'Loaded',
    screen,
    properties: {
      productListVisible,
      experimentName: versioningExperimentName,
      numberOfProducts,
      cardSetType,
    },
    addUserProperties: true,
  });
};

export const trackProductVersioningWidgetClicked = ({ productClicked, clickButtonName, cardSetType, screen = 'home page' }) => {
  analyticsTrackWithUserInfo({
    objectName: 'Product Versioning Widget',
    actionName: 'Clicked',
    screen,
    properties: {
      productClicked,
      experimentName: versioningExperimentName,
      clickButtonName,
      cardSetType,
    },
    addUserProperties: true,
  });
};

export const trackProductVersioningWidgetFlipAction = ({ productClicked, actionName, cardSetType, screen = 'home page' }) => {
  analyticsTrackWithUserInfo({
    objectName: 'Product Versioning Widget',
    actionName,
    screen,
    properties: {
      productClicked,
      experimentName: versioningExperimentName,
      cardSetType,
    },
    addUserProperties: true,
  });
}; 