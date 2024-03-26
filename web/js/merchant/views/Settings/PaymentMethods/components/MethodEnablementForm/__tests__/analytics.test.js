import * as analytics from 'common/utils/analytics';
import {
  trackPrerequisiteClicked,
  trackVideoKycRetryClick,
  trackVideoKycLinkGeneration,
  trackSubmitForVerificationClicked,
  trackIntlMethodEnablementFormData,
  trackVkycStatusResponse,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/analytics';

const trackSpy = jest.spyOn(analytics, 'analyticsTrack');

const commonProperties = {
  properties: { section: 'International Payments', subSection: 'Additional method enablement' },
  screen: 'Account & Settings',
};

const businessType = '1';

describe('trackPrerequisiteClicked', () => {
  test('should call track function with correct parameters', () => {
    trackPrerequisiteClicked(businessType);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement prerequisite info',
      actionName: 'clicked',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
      },
    });
  });
});

describe('trackVideoKycRetryClick', () => {
  test('should call track function with correct parameters', () => {
    trackVideoKycRetryClick(businessType);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement video kyc retry',
      actionName: 'clicked',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
      },
    });
  });
});

describe('trackVideoKycLinkGeneration', () => {
  test('should track successfull vcip link creation', () => {
    trackVideoKycLinkGeneration(businessType, false);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement video kyc',
      actionName: 'response',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
        authorized_signatory: false,
      },
    });
  });

  test('should track error while vcip link creation', () => {
    const errorDescription = 'something went wring';
    const errorCode = '500';
    trackVideoKycLinkGeneration(businessType, false, errorCode, errorDescription);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement video kyc',
      actionName: 'response',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
        authorized_signatory: false,
        error_code: errorCode,
        error_description: errorDescription,
      },
    });
  });
});

describe('trackSubmitForVerificationClicked', () => {
  test('should call track function with correct parameters', () => {
    trackSubmitForVerificationClicked(businessType);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement upload kyc docs',
      actionName: 'clicked',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
      },
    });
  });
});

describe('trackIntlMethodEnablementFormData', () => {
  test('should call track function with correct parameters', () => {
    const data = { name: 'dummy name' };
    trackIntlMethodEnablementFormData(businessType, data);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement form data',
      actionName: 'saved',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
        ...data,
      },
    });
  });
});

describe('trackVkycStatusResponse', () => {
  test('should track sucessfull vcip status call', () => {
    const vcipStatus = 'activated';
    trackVkycStatusResponse(businessType, vcipStatus);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement video kyc',
      actionName: 'response',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
        vcip_status: vcipStatus,
      },
    });
  });

  test('should track failed vcip status call', () => {
    const vcipStatus = '';
    const errorDescription = 'something went wring';
    const errorCode = '500';
    trackVkycStatusResponse(businessType, vcipStatus, errorCode, errorDescription);

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement video kyc',
      actionName: 'response',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
        vcip_status: vcipStatus,
        error_code: errorCode,
        error_description: errorDescription,
      },
    });
  });
});
