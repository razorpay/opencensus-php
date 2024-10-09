export const workflowDataMock = {
  id: 'P0xLJIwqevpAmZ',
  progress: 56.25,
  status: 'processing',
  milestones: [
    {
      canSubmit: false,
      name: 'partner_milestone',
      status: 'processing',
      steps: [
        {
          __typename: 'ModularOnboardingStepWithModularComponents',
          name: 'device_deployment_step',
          progress: 45,
          status: 'processing',
          meta: {
            title: '6. Device Deployment',
            description: "Provide merchant's business information to start the POS journey",
          },
          modularComponents: [
            {
              fields: [
                {
                  name: 'device_language_field',
                  isDisabled: false,
                  isRequired: false,
                  isHidden: false,
                  meta: {
                    title: 'DEVICE DEPLOYMENT',
                    description: 'Preferred Language',
                    defaultValue: null,
                    size: null,
                    accessibilityLabel: null,
                    hideOnReviewScreen: null,
                    dataType: 'radio',
                    selectionType: null,
                    options: [
                      {
                        label: 'English',
                        value: 'English',
                      },
                      {
                        label: 'Hindi',
                        value: 'Hindi',
                      },
                    ],
                    validations: null,
                    jsonValue: {
                      data_type: 'radio',
                      description: 'Preferred Language',
                      is_hidden: false,
                      options: [
                        {
                          label: 'English',
                          value: 'English',
                        },
                        {
                          label: 'Hindi',
                          value: 'Hindi',
                        },
                      ],
                      title: 'DEVICE DEPLOYMENT',
                    },
                  },
                  failureReason: '',
                  failureReasonType: '',
                  stringValue: 'Hindi',
                },
              ],
              meta: {
                description: 'Device Details',
                errorCode: null,
                isHidden: null,
                template: 'grid',
                title: 'DEVICE DEPLOYMENT',
                validations: null,
                defaultValues: null,
                deviceConfig: null,
                metaUi: null,
              },
              progress: 45,
              status: 'processing',
              name: 'device_deployment_component',
            },
          ],
        },
      ],
    },
  ],
};
export const ERROR_MODULAR_RESPONSE = {
  merchantModularOnboardingDetailsAsSales: {
    __typename: 'merchantModularOnboardingDetailsFailureResponse',
    code: 200,
    success: false,
    message: 'Failed to fetch modular config',
  },
};

export const SUCCESS_MODULAR_RESPONSE = {
  merchantModularOnboardingDetailsAsSales: {
    __typename: 'merchantModularOnboardingDetailsSuccessResponse',
    success: true,
    workflowData: workflowDataMock,
    onboardingState: {
      milestones: ['sales_milestone'],
      modularComponents: [
        'qr_code_component',
        'acquisition_model_component',
        'agreement_component',
        'consent_component',
        'additional_details_component',
      ],
      steps: [
        'device_selection_step',
        'pricing_step',
        'agreement_step',
        'consent_step',
        'additional_details_step',
      ],
    },
  },
};
