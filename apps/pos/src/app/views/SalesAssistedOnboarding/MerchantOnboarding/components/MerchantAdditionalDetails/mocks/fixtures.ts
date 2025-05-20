export const getMockModularConfigWithAdditionalDetailsStep = ({
  addedDevices = [
    {
      deviceName: 'Android Smart Pos',
      itemId: '59f0f5dc-d53c-446c-9188-5ae6961a02dd',
      paperRollCharge: 15,
      paperRollQuantity: 0,
      quantity: 1,
      renewal: 'monthly',
      rentalCharge: 80,
      setupCharge: 200,
      totalAdvanceRentalCharge: 160,
      totalPaperRollCharge: 0,
      totalRentalCharge: 94.4,
      totalSetupCharge: 200,
      rentalChargeType: 'custom',
      setupChargeType: 'custom',
    },
  ],
}: any) => ({
  merchantModularOnboardingDetailsAsSales: {
    __typename: 'merchantModularOnboardingDetailsSuccessResponse',
    workflowData: {
      id: 'Oui334',
      milestones: [
        {
          steps: [
            {
              __typename: 'ModularOnboardingStepWithModularComponents',
              name: 'additional_details_step',
              progress: 7.5,
              status: 'processing',
              meta: {
                title: '5. Additional Details',
                description:
                  'Help your merchants optimise their transactions with the perfect POS devices',
              },
              modularComponents: [
                {
                  fields: [
                    {
                      name: 'additional_details_annual_turnover_field',
                      isDisabled: false,
                      isRequired: true,
                      isHidden: false,
                      meta: {
                        title: 'Annual Turnover',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'select',
                        options: [
                          {
                            label: 'Less than or Equal to ₹20L',
                            value: 'less_than_or_equal_to_20l',
                          },
                          {
                            label: 'More than ₹20L',
                            value: 'more_than_20l',
                          },
                        ],
                        validations: [
                          {
                            condition: true,
                            errorMessage: 'Annual Turnover is required',
                            type: 'isRequired',
                          },
                        ],
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: 'more_than_20l',
                    },
                    {
                      name: 'additional_details_acquirer_preference_field',
                      isDisabled: false,
                      isRequired: true,
                      isHidden: false,
                      meta: {
                        title: 'Acquirer Preference',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'select',
                        options: [
                          {
                            label: 'HDFC',
                            value: 'hdfc',
                          },
                          {
                            label: 'Axis',
                            value: 'axis',
                          },
                          {
                            label: 'SBI',
                            value: 'sbi',
                          },
                          {
                            label: 'Kotak',
                            value: 'kotak',
                          },
                          {
                            label: 'APB',
                            value: 'apb',
                          },
                        ],
                        validations: [
                          {
                            condition: true,
                            errorMessage: 'Acquirer Preference is required',
                            type: 'isRequired',
                          },
                        ],
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: 'sbi',
                    },
                    {
                      name: 'additional_details_cashier_name_field',
                      isDisabled: false,
                      isRequired: true,
                      isHidden: false,
                      meta: {
                        title: 'Name of Store Manager/ Cashier',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'string',
                        options: null,
                        validations: [
                          {
                            condition: true,
                            errorMessage: 'Name of Store Manager/Cashier is required',
                            type: 'isRequired',
                          },
                        ],
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: 'Anant Ambani',
                    },
                    {
                      name: 'additional_details_cashier_mobile_number_field',
                      isDisabled: false,
                      isRequired: true,
                      isHidden: false,
                      meta: {
                        title: 'Mobile Number of Store Manager/ Cashier',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'string',
                        options: null,
                        validations: [
                          {
                            condition: true,
                            errorMessage: 'Mobile Number of Store Manager/Cashier is required',
                            type: 'isRequired',
                          },
                        ],
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: '6234567890',
                    },
                    {
                      name: 'additional_details_marketing_plan_field',
                      isDisabled: false,
                      isRequired: true,
                      isHidden: false,
                      meta: {
                        title: 'Marketing Plan',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'select',
                        options: [
                          {
                            label: 'Standard Plan',
                            value: 'standard_plan',
                          },
                          {
                            label: 'Feb Campaign',
                            value: 'feb_campaign',
                          },
                          {
                            label: 'Brand EMI with advance rental 499(non-Xiaomi / Samsung EBO)',
                            value: 'brand_emi_with_advance_rental_499',
                          },
                          {
                            label: 'Xiaomi-Samsung Brand EMI (Zero Advance rental)',
                            value: 'xiaomi_samsung_brand_emi_zero_rental',
                          },
                          {
                            label: 'RBPML',
                            value: 'rbpml',
                          },
                          {
                            label: 'Lifetime (3yrs)',
                            value: 'lifetime_3_years',
                          },
                          {
                            label: 'Jana Bank (SMP)',
                            value: 'jana_bank_smp',
                          },
                          {
                            label: 'Jana Bank (Non-SMP)',
                            value: 'jana_bank_non_smp',
                          },
                          {
                            label: 'Oritso',
                            value: 'oritso',
                          },
                        ],
                        validations: [
                          {
                            condition: true,
                            errorMessage: 'Marketing Plan is required',
                            type: 'isRequired',
                          },
                        ],
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: 'jana_bank_non_smp',
                    },
                    {
                      name: 'additional_details_si_partner_field',
                      isDisabled: false,
                      isRequired: false,
                      isHidden: false,
                      meta: {
                        title: 'SI Partner',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'string',
                        options: null,
                        validations: null,
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: '',
                    },
                    {
                      name: 'additional_details_partner_field',
                      isDisabled: false,
                      isRequired: false,
                      isHidden: false,
                      meta: {
                        title: 'Referral Partner',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'select',
                        options: [
                          {
                            label: 'ITC',
                            value: 'itc',
                          },
                          {
                            label: 'Jana Bank',
                            value: 'jana',
                          },
                        ],
                        validations: null,
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: '',
                    },
                    {
                      name: 'additional_details_omc_field',
                      isDisabled: false,
                      isRequired: false,
                      isHidden: false,
                      meta: {
                        title: 'OMC',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'select',
                        options: [
                          {
                            label: 'RBPML',
                            value: 'rbpml',
                          },
                          {
                            label: 'IOCL',
                            value: 'iocl',
                          },
                          {
                            label: 'Nayara',
                            value: 'nayara',
                          },
                          {
                            label: 'BPCL',
                            value: 'bpcl',
                          },
                          {
                            label: 'HPCL',
                            value: 'hpcl',
                          },
                        ],
                        validations: null,
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: 'iocl',
                    },
                    {
                      name: 'additional_details_sap_code_field',
                      isDisabled: false,
                      isRequired: false,
                      isHidden: false,
                      meta: {
                        title: 'SAP Code',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'string',
                        options: null,
                        validations: null,
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: '123456789',
                    },
                    {
                      name: 'additional_details_solution_type_field',
                      isDisabled: false,
                      isRequired: true,
                      isHidden: false,
                      meta: {
                        title: 'Solution Type',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'radio',
                        options: [
                          {
                            label: 'Non-Integrated',
                            value: 'non_integrated',
                          },
                          {
                            label: 'Integrated',
                            value: 'integrated',
                          },
                        ],
                        validations: null,
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringValue: 'integrated',
                    },
                  ],
                  meta: {
                    description:
                      'Help your merchants optimise their transactions with the perfect POS devices',
                    errorCode: null,
                    isHidden: null,
                    template: 'grid',
                    title: '5. Additional Details',
                    validations: null,
                    deviceConfig: null,
                    metaUi: null,
                    acquirerPreferenceOptions: [
                      {
                        label: 'SBI',
                        value: 'sbi',
                      },
                    ],
                  },
                  progress: 7.5,
                  status: 'processing',
                  name: 'additional_details_component',
                },
                {
                  fields: [
                    {
                      name: 'nach_form_document_field',
                      isDisabled: false,
                      isRequired: true,
                      isHidden: false,
                      meta: {
                        title: 'Nach form document',
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'select',
                        options: [
                          {
                            label: 'Less than or Equal to ₹20L',
                            value: 'less_than_or_equal_to_20l',
                          },
                          {
                            label: 'More than ₹20L',
                            value: 'more_than_20l',
                          },
                        ],
                        validations: [
                          {
                            condition: true,
                            errorMessage: 'Annual Turnover is required',
                            type: 'isRequired',
                          },
                        ],
                      },
                      failureReason: '',
                      failureReasonType: '',
                      stringArrayValue: [
                        {
                          fileId: 'QB0GAkMLKJGFzj-sales-file',
                          fileStoreId: 'QB0GAkMLKJGFzj',
                          name: '2.png',
                          size: 103657,
                        },
                      ],
                    },                   
                  ],
                  meta: {
                    description:
                      'Help your merchants optimise their transactions with the perfect POS devices',
                    errorCode: null,
                    isHidden: null,
                    template: 'grid',
                    title: '5. Additional Details',
                    validations: null,
                    deviceConfig: null,
                    metaUi: null,
                    acquirerPreferenceOptions: [
                      {
                        label: 'SBI',
                        value: 'sbi',
                      },
                    ],
                  },
                  progress: 7.5,
                  status: 'processing',
                  name: 'nach_form_component', //nach component in this step comes for partnership flow
                },
              ],
            },
            {
              __typename: 'ModularOnboardingStepWithModularComponents',
              name: 'device_selection_step',
              progress: 77.053569999999993,
              status: 'processing',
              meta: {
                title: '2. Device Selection & Ordering',
                description:
                  'Help your merchants optimise their transactions with the perfect POS devices',
              },
              modularComponents: [
                {
                  fields: [
                    {
                      name: 'device_order_items_summary_field',
                      isDisabled: false,
                      isRequired: true,
                      isHidden: false,
                      meta: {
                        title: null,
                        description: null,
                        defaultValue: null,
                        size: null,
                        accessibilityLabel: null,
                        hideOnReviewScreen: null,
                        dataType: 'deviceOrderItemSummaryList',
                        selectionType: null,
                        options: null,
                        validations: null,
                        jsonValue: {
                          data_type: 'deviceOrderItemSummaryList',
                        },
                      },
                      failureReason: '',
                      failureReasonType: '',
                      addedDevices: addedDevices,
                    },
                  ],
                  meta: {
                    description:
                      'Help your merchants optimise their transactions with the perfect POS devices',
                    errorCode: null,
                    isHidden: null,
                    template: 'grid',
                    title: '2. Device Selection & Ordering',
                    validations: null,
                    deviceConfig: null,
                    metaUi: null,
                  },
                  progress: 77.053569999999993,
                  status: 'processing',
                  name: 'device_catalogue_component',
                },
              ],
            },
          ],
        },
      ],
    },
  },
});

export const getMockUseOnboardingContext = () => ({
  values: {
    isNewOnboarding: false,
    merchantId: 'PhjLpFaE7tz4cA',
    onboardingSteps: [],
  },
  states: {
    isModularLoading: false,
    isUpdateModularLoading: false,
    isModularFetchError: false,
    isRefetching: false,
    merchantDetails:{
      activation:{
        posActivationStatus: 'pending'
      }
    },
    modularConfig: getMockModularConfigWithAdditionalDetailsStep({}).merchantModularOnboardingDetailsAsSales,
    isPosEkycAgent: false,
  },
  handlers: {
    getOnboardingProgress: () => ({ totalSteps: 6, totalCompletedSteps: 2 }),
    handleStepClick: () => ({}),
    updateModularConfig: (payload: any) => ({ ...payload }),
    handleProceedToNextComponent: () => ({}),
    refetchModularConfig: () => ({}),
    getStepConfigStepSlug: () => ({}),
  },
});
