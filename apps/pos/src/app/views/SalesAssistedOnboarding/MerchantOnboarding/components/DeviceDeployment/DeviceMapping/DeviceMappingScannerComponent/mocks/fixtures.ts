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
                  name: 'current_device_id_field',
                  isDisabled: false,
                  isRequired: false,
                  isHidden: false,
                  meta: {
                    title: null,
                    description: null,
                    defaultValue: null,
                    size: null,
                    accessibilityLabel: null,
                    hideOnReviewScreen: null,
                    dataType: 'string',
                    selectionType: null,
                    options: null,
                    validations: null,
                    jsonValue: {
                      data_type: 'string',
                      device_deployment_details: {
                        details_page_name: 'WD10 SQR Soundbox',
                        device_model: 'wd10',
                        device_order_item_id: 'P0G3x6AuQoY9zy',
                        device_serial: 'test78990',
                        display_label: 'Soundbox + Sticker + Standee',
                        display_name: 'Soundbox Kit',
                        icon: 'https://betacdn.np.razorpay.in/static/assets/pos/sales-assisted/soundbox-kit.png',
                        id: 'P0G3x7msqXEAWw',
                        mapped_vpa: 'sse14v37w672y130',
                        mapping_status: 'DEPLOYED',
                        plan_name: '',
                        setup_charge: 0,
                      },
                      is_hidden: true,
                      merchant_id: 'P0G1uuiS12ruFF',
                    },
                  },
                  failureReason: '',
                  failureReasonType: '',
                  jsonValue: 'P0G3x7msqXEAWw',
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
  },
};
