import * as analytics from 'common/utils/analytics';
import { dateObject } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import {
  trackDownloadPopupOpened,
  trackDownloadPopupClosed,
  trackDownloadFirsClicked,
  trackDownloadFirsResponse,
  trackRequestFirsButtonClick,
  trackRequestFirsResponse,
} from 'merchant/views/AccountAndSettings/InternationalSettings/analytics';

const trackSpy = jest.spyOn(analytics, 'analyticsTrack');

const commonProperties = {
  properties: {},
  screen: 'Account & Settings',
};

describe('Tests for analytics functions', () => {
  const { month, year } = dateObject;
  const fileType = 'Dummy Type';
  const status = 'Dummy Status';

  const sectionProperties = {
    section: 'International payments settings',
    subSection: 'Foreign inward remittance statement',
  };

  test('should call trackDownloadPopupOpened with correct parameters', () => {
    trackDownloadPopupOpened(month, year);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'firs download popup',
      actionName: 'opened',
      properties: {
        ...sectionProperties,
        month,
        year,
      },
    });
  });

  test('should call trackDownloadPopupClosed with correct parameters', () => {
    trackDownloadPopupClosed(month, year);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'firs download popup',
      actionName: 'closed',
      properties: {
        ...sectionProperties,
        month,
        year,
      },
    });
  });

  test('should call trackDownloadFirsClicked with correct parameters', () => {
    trackDownloadFirsClicked(month, year, fileType);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'firs file download',
      actionName: 'clicked',
      properties: {
        ...sectionProperties,
        month,
        year,
        fileType,
      },
    });
  });

  test('should call trackDownloadFirsResponse with correct parameters', () => {
    trackDownloadFirsResponse(month, year, fileType, status);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'firs file download',
      actionName: 'response',
      properties: {
        ...sectionProperties,
        month,
        year,
        fileType,
        status,
      },
    });
  });

  test('should call trackRequestFirsButtonClick with correct parameters', () => {
    trackRequestFirsButtonClick(month, year);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'internal firs request',
      actionName: 'clicked',
      properties: {
        ...sectionProperties,
        month,
        year,
      },
    });
  });

  test('should call trackRequestFirsResponse with correct parameters', () => {
    trackRequestFirsResponse(month, year, status);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'internal firs request',
      actionName: 'response',
      properties: {
        ...sectionProperties,
        month,
        year,
        status,
      },
    });
  });
});
