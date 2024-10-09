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
                  name: 'device_deployment_details_list_field',
                  isDisabled: true,
                  isRequired: false,
                  isHidden: false,
                  meta: {
                    title: null,
                    description: null,
                    defaultValue: null,
                    size: null,
                    accessibilityLabel: null,
                    hideOnReviewScreen: null,
                    dataType: 'custom',
                    selectionType: null,
                    options: null,
                    validations: null,
                    jsonValue: {
                      data_type: 'custom',
                      device_deployment_details_list: [
                        {
                          details_page_name: 'Sticker and Standee',
                          device_model: 'rzp qr sticker and standee',
                          device_order_item_id: 'P0G3x6alJNR1hd',
                          device_serial: '',
                          display_label: '',
                          display_name: 'Sticker and Standee',
                          icon: 'https://betacdn.np.razorpay.in/static/assets/pos/sales-assisted/sticker-standee-qr.png',
                          id: 'P0G3x7Ev85AVFL',
                          mapped_vpa: 'sse14v37w672y130',
                          mapping_status: 'DEPLOYED',
                          plan_name: '',
                          setup_charge: 0,
                        },
                        {
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
                      ],
                      merchant_id: 'P0G1uuiS12ruFF',
                    },
                  },
                  failureReason: '',
                  failureReasonType: '',
                  jsonValue: {
                    device_deployment_details_list: null,
                  },
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
