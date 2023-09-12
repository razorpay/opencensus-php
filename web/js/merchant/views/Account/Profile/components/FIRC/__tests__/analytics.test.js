import * as analytics from 'common/utils/analytics';
import {
  trackGoToIECCodeStep,
  trackPurposeCodeSaved,
  trackPurposeCodeSearched,
  trackPurposeCodeSelected,
  trackPurposeCodePopupMode,
  trackPurposeCodePopupOpened,
  trackPurposeCodePopupClosed,
  trackPurposeCodeSavingFailed,
  trackPurposeCodeSearchCleared,
  trackPurposeCodeUpdateRequestRaised,
  trackPurposeCodeUpdateRequestFailed,
} from 'merchant/views/Account/Profile/components/FIRC/analytics';

const trackSpy = jest.spyOn(analytics, 'analyticsTrack');

const commonProperties = {
  properties: {},
  screen: 'Account & Settings',
};

describe('trackPurposeCodePopupOpened', () => {
  test('should call track function with correct parameters', () => {
    trackPurposeCodePopupOpened();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup opened',
      actionName: 'clicked',
    });
  });
});

describe('trackPurposeCodePopupClosed', () => {
  test('should call track function with correct parameters', () => {
    trackPurposeCodePopupClosed();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup closed',
      actionName: 'clicked',
    });
  });
});

describe('trackPurposeCodePopupMode', () => {
  test('should track new mode', () => {
    trackPurposeCodePopupMode(false);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup mode',
      actionName: 'new',
      properties: {
        oldPurposeCode: undefined,
      },
    });
  });
  test('should track edit mode', () => {
    trackPurposeCodePopupMode(true, 'P1012');

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup mode',
      actionName: 'edit',
      properties: {
        oldPurposeCode: 'P1012',
      },
    });
  });
});

describe('trackPurposeCodeSelected', () => {
  test('should track selected purpose code', () => {
    trackPurposeCodeSelected('P891');

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup',
      actionName: 'selected',
      properties: {
        newPurposeCode: 'P891',
      },
    });
  });
});

describe('trackPurposeCodeSaved', () => {
  test('should track purpose code saved', () => {
    trackPurposeCodeSaved('S23112');
    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup save',
      actionName: 'success',
      properties: {
        purposeCode: 'S23112',
      },
    });
  });
});

describe('trackPurposeCodeSavingFailed', () => {
  test('should track purpose code saving failed', () => {
    trackPurposeCodeSavingFailed('S23112', 'error reason');
    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup save',
      actionName: 'failed',
      properties: {
        purposeCode: 'S23112',
        errorReason: 'error reason',
      },
    });
  });
});

describe('trackPurposeCodeUpdateRequestRaised', () => {
  test('should track update purpose code request', () => {
    trackPurposeCodeUpdateRequestRaised('P12', 'S89');
    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup support request',
      actionName: 'success',
      properties: {
        newPurposeCode: 'P12',
        oldPurposeCode: 'S89',
      },
    });
  });
});

describe('trackPurposeCodeUpdateRequestFailed', () => {
  test('should track update purpose code request failed', () => {
    trackPurposeCodeUpdateRequestFailed('P12', 'S89');
    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup support request',
      actionName: 'failed',
      properties: {
        newPurposeCode: 'P12',
        oldPurposeCode: 'S89',
      },
    });
  });
});

describe('trackGoToIECCodeStep', () => {
  test('should track update purpose code request failed', () => {
    trackGoToIECCodeStep('P12', 'S89');
    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup iec code',
      actionName: 'step',
      properties: {
        specialPurposeCode: true,
      },
    });
  });
});

describe('trackPurposeCodeSearched', () => {
  test('should track update purpose code request failed', () => {
    trackPurposeCodeSearched();
    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup search',
      actionName: 'input',
    });
  });
});

describe('trackPurposeCodeSearchCleared', () => {
  test('should track update purpose code request failed', () => {
    trackPurposeCodeSearchCleared();
    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'purpose code popup search',
      actionName: 'cleared',
    });
  });
});
