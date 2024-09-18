import * as analytics from 'common/utils/analytics';
import {
  trackPrerequisiteClicked,
  trackVideoKycRetryClick,
  trackVideoKycLinkGeneration,
  trackSubmitForVerificationClicked,
  trackIntlMethodEnablementFormData,
  trackVkycStatusResponse,
  trackDocumentUploadSuccess,
  trackDocumentUploadErr,
  trackIntlMethodEnablementFormDataErr,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/analytics';

const trackSpy = jest.spyOn(analytics, 'analyticsTrack');

const commonProperties = {
  properties: { section: 'International Payments', subSection: 'Additional method enablement' },
  screen: 'Account & Settings',
};

const businessType = '1';
const documentType = 'passport';

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

  test('should track error on submission', () => {
    const formData = { name: 'dummy name' };
    const error = { status_code: '500', errors: ['something went wrong'] };
    trackIntlMethodEnablementFormDataErr({
      business_type: businessType,
      ...formData,
      error,
    });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement form data',
      actionName: 'error',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
        ...formData,
        error_code: error?.status_code,
        error_description: error?.errors?.[0],
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
    const errorDescription = 'something went wrong';
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

describe('trackDocumentUpload', () => {
  test('should track success response', () => {
    trackDocumentUploadSuccess({ businessType, documentType });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement additional document',
      actionName: 'success',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
        document_type: documentType,
      },
    });
  });

  test('should track failure response', () => {
    const error = { status_code: '500', errors: ['Document upload failed'] };
    trackDocumentUploadErr({
      businessType,
      documentType,
      error,
    });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'international additional methods enablement additional document',
      actionName: 'error',
      properties: {
        ...commonProperties.properties,
        business_type: businessType,
        document_type: documentType,
        error_code: error?.status_code,
        error_description: error?.errors?.[0],
      },
    });
  });
});
