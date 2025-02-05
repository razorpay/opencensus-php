export const additionalDetailsMock = {
  __typename: 'ModularOnboardingStepWithModularComponents',
  name: 'additional_details_step',
  progress: 100,
  status: 'executed',
  meta: {
    title: '5. Additional Details',
    description: 'Help your merchants optimise their transactions with the perfect POS devices',
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
            selectionType: null,
            options: [
              {
                label: 'Less than or Equal to \u20b920L',
                value: 'less_than_or_equal_to_20l',
              },
              {
                label: 'More than \u20b920L',
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
            jsonValue: {
              data_type: 'select',
              options: [
                {
                  label: 'Less than or Equal to \u20b920L',
                  value: 'less_than_or_equal_to_20l',
                },
                {
                  label: 'More than \u20b920L',
                  value: 'more_than_20l',
                },
              ],
              title: 'Annual Turnover',
              validations: [
                {
                  condition: true,
                  errorMessage: 'Annual Turnover is required',
                  type: 'isRequired',
                },
              ],
            },
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: 'less_than_or_equal_to_20l',
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
            selectionType: null,
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
            jsonValue: {
              data_type: 'select',
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
              title: 'Acquirer Preference',
              validations: [
                {
                  condition: true,
                  errorMessage: 'Acquirer Preference is required',
                  type: 'isRequired',
                },
              ],
            },
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: 'axis',
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
            selectionType: null,
            options: null,
            validations: [
              {
                condition: true,
                errorMessage: 'Name of Store Manager/Cashier is required',
                type: 'isRequired',
              },
            ],
            jsonValue: {
              data_type: 'string',
              title: 'Name of Store Manager/ Cashier',
              validations: [
                {
                  condition: true,
                  errorMessage: 'Name of Store Manager/Cashier is required',
                  type: 'isRequired',
                },
              ],
            },
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: 'john',
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
            selectionType: null,
            options: null,
            validations: [
              {
                condition: true,
                errorMessage: 'Mobile Number of Store Manager/Cashier is required',
                type: 'isRequired',
              },
            ],
            jsonValue: {
              data_type: 'string',
              title: 'Mobile Number of Store Manager/ Cashier',
              validations: [
                {
                  condition: true,
                  errorMessage: 'Mobile Number of Store Manager/Cashier is required',
                  type: 'isRequired',
                },
              ],
            },
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: '8775637732',
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
            selectionType: null,
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
            jsonValue: {
              data_type: 'select',
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
              title: 'Marketing Plan',
              validations: [
                {
                  condition: true,
                  errorMessage: 'Marketing Plan is required',
                  type: 'isRequired',
                },
              ],
            },
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: 'standard_plan',
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
            selectionType: null,
            options: null,
            validations: null,
            jsonValue: {
              data_type: 'string',
              title: 'SI Partner',
            },
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
            selectionType: null,
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
            jsonValue: {
              data_type: 'select',
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
              title: 'Referral Partner',
            },
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
            selectionType: null,
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
            jsonValue: {
              data_type: 'select',
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
              title: 'OMC',
            },
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: '',
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
            selectionType: null,
            options: null,
            validations: null,
            jsonValue: {
              data_type: 'string',
              title: 'SAP Code',
            },
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: '',
        },
        {
          name: 'additional_details_solution_type_field',
          isDisabled: false,
          isRequired: false,
          isHidden: false,
          meta: {
            title: 'Solution Type',
            description: null,
            defaultValue: null,
            size: null,
            accessibilityLabel: null,
            hideOnReviewScreen: null,
            dataType: 'radio',
            selectionType: null,
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
            jsonValue: {
              data_type: 'radio',
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
              title: 'Solution Type',
            },
          },
          failureReason: '',
          failureReasonType: '',
          stringValue: 'integrated',
        },
      ],
      meta: {
        description: 'Help your merchants optimise their transactions with the perfect POS devices',
        errorCode: null,
        isHidden: null,
        template: 'linear',
        title: '5. Additional Details',
        validations: null,
        defaultValues: null,
        brandDataFields: null,
        merchantGstField: null,
        deviceConfig: null,
        metaUi: null,
        acquirerPreferenceOptions: [
          {
            label: 'None',
            value: 'none',
          },
          {
            label: 'HDFC',
            value: 'hdfc',
          },
          {
            label: 'Axis',
            value: 'axis',
          },
          {
            label: 'APB',
            value: 'apb',
          },
        ],
      },
      progress: 100,
      status: 'executed',
      name: 'additional_details_component',
    },
  ],
};