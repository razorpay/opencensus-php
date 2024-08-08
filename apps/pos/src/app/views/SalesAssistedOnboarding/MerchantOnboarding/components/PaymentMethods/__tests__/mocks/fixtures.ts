interface Documents {
  name: string;
  size: number;
  fileStoreId: string;
}
export interface GetMockModularResponseProps {
  agreementType?: string;
  agreementComponentStatus?: string;
  agreementSentAt?: string;
  agreementConsentedAt?: string;
  agreementDocuments?: Documents[];
  agreementStatusField?: string;
  retrySentAt?: string;

  acquisition_model_field?: 'aggregator' | 'direct';
}
export const getMockModularResponse = ({
  acquisition_model_field = 'direct',
}: GetMockModularResponseProps) => ({
  id: 'Oe0O6xyCfmHWdv',
  progress: 5,
  status: 'processing',
  milestones: [
    {
      can_submit: true,
      meta: {
        template: 'grid',
        title: 'Merchant Onboarding',
      },
      name: 'sales_milestone',
      progress: 100,
      status: 'executed',
      steps: [
        {
          components: [
            {
              fields: [
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'radio',
                    options: [
                      {
                        label: 'Aggregator Model',
                        value: 'aggregator',
                      },
                      {
                        label: 'Direct Model',
                        value: 'direct',
                      },
                    ],
                    selection_type: 'single',
                    title: 'Select Onboarding Model',
                  },
                  name: 'acquisition_model_field',
                  user_comments: '',
                  value: acquisition_model_field,
                },
              ],
              is_required: true,
              meta: {
                description: 'Choose payment methods & review MDR rates',
                is_custom_pricing_applicable: true,
                template: 'linear',
                title: '3. Payment Method & Service Selection',
              },
              name: 'acquisition_model_component',
              progress: 100,
              status: 'executed',
              verification: null,
            },
            {
              fields: [
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: false,
                  meta: {
                    data_type: 'checkbox',
                    title: 'Custom rated applicable for merchants',
                  },
                  name: 'custom_rates_enabled_field',
                  user_comments: '',
                  value: true,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'file_upload',
                    upload_type: 'multiple',
                    validations: [
                      {
                        condition: '5 MB',
                        errorMessage: 'File size greater than 5 MB',
                        type: 'maxFileSize',
                      },
                      {
                        condition: '5',
                        errorMessage: 'No of files greater than 5',
                        type: 'maxFiles',
                      },
                    ],
                  },
                  name: 'custom_rates_documents_field',
                  user_comments: '',
                  value: [
                    {
                      file_id: '28xi4gJ3KBJqJY',
                      file_store_id: '28xi4gJ3KBJqJY',
                      name: 'custom_rates_proof.pdf',
                      size: 0,
                    },
                  ],
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: true,
                  is_required: false,
                  meta: {
                    data_type: 'string',
                  },
                  name: 'previous_custom_rates_documents_field',
                  user_comments: '',
                  value: [
                    {
                      file_id: '28xi4gJ3KBJqJY',
                      file_store_id: '28xi4gJ3KBJqJY',
                      name: 'custom_rates_proof.pdf',
                      size: 0,
                    },
                  ],
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'float',
                    title: 'Debit Card (Rupay)',
                  },
                  name: 'debit_card_rupay_mdr_rate_field',
                  user_comments: '',
                  value: 1,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'float',
                    description: 'Greater than 2k transaction amount size',
                    title: 'Debit Card (VISA/ MasterCard/ Maestro)',
                  },
                  name: 'debit_card_visa_mastercard_maestro_greater_than_2k_mdr_rate_field',
                  user_comments: '',
                  value: 1.1,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'float',
                    description: 'Less than 2k transaction amount size',
                    title: 'Debit Card (VISA/ MasterCard/ Maestro)',
                  },
                  name: 'debit_card_visa_mastercard_maestro_less_than_2k_mdr_rate_field',
                  user_comments: '',
                  value: 1.2,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'float',
                    description: 'Auto populated as per MCC',
                    title: 'Credit Card',
                  },
                  name: 'credit_card_mdr_rate_field',
                  user_comments: '',
                  value: 1,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'float',
                    title: 'Prepaid/ B2B/ Corporate/ Channel Cards/ International Cards',
                  },
                  name: 'prepaid_b2b_corporate_channel_international_card_mdr_rate_field',
                  user_comments: '',
                  value: 1.3,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'float',
                    title: 'UPI',
                  },
                  name: 'upi_mdr_rate_field',
                  user_comments: '',
                  value: 0.5,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'float',
                    title: 'CC EMI',
                  },
                  name: 'vas_cc_emi_rate_field',
                  user_comments: '',
                  value: 1.4,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'float',
                    title: 'DC EMI',
                  },
                  name: 'vas_dc_emi_rate_field',
                  user_comments: '',
                  value: 1.3,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'checkbox',
                  },
                  name: 'vas_cc_emi_rate_enabled_field',
                  user_comments: '',
                  value: true,
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'checkbox',
                  },
                  name: 'vas_dc_emi_rate_enabled_field',
                  user_comments: '',
                  value: true,
                },
              ],
              is_required: true,
              meta: {
                default_values: {
                  cc_emi: 0,
                  corporate_card: 0,
                  credit_card: 0,
                  dc_emi: 0,
                  debit_card_rupay: 0,
                  debit_card_visa_master_less_than_2k: 0,
                  debit_card_visa_master_more_than_2k: 0,
                  upi: 0,
                },
                description: 'Choose MDR Rates & Value Added Services',
                is_custom_pricing_applicable: true,
                template: 'linear',
                title: 'Payment Method & Service Selection',
              },
              name: 'mdr_vas_rates_component',
              progress: 100,
              status: 'executed',
              verification: null,
            },
            {
              fields: [
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: true,
                  meta: {
                    data_type: 'file_upload',
                    description:
                      "TO debit the renatal charges from Merchant's account automatically",
                    title: 'Upload NACH Form',
                    upload_type: 'single',
                    validations: [
                      {
                        condition: '5 MB',
                        errorMessage: 'File size greater than 5 MB',
                        type: 'maxFileSize',
                      },
                    ],
                  },
                  name: 'nach_form_document_field',
                  user_comments: '',
                  value: {
                    file_id: 'Hd9okiPyw50CiA',
                    file_store_id: 'Hd9okiPyw50CiA',
                    name: 'nach.pdf',
                    size: 3,
                  },
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: true,
                  is_required: false,
                  meta: {
                    data_type: 'string',
                  },
                  name: 'previous_nach_form_document_field',
                  user_comments: '',
                  value: {
                    file_id: 'Hd9okiPyw50CiA',
                    file_store_id: 'Hd9okiPyw50CiA',
                    name: 'nach.pdf',
                    size: 3,
                  },
                },
                {
                  failure_reason: '',
                  failure_reason_type: '',
                  is_editable: true,
                  is_hidden: false,
                  is_internal: false,
                  is_required: false,
                  meta: {
                    data_type: 'string',
                    title: 'Additional Sales Comments',
                  },
                  name: 'nach_form_comments_field',
                  user_comments: '',
                  value: 'hello',
                },
              ],
              is_required: false,
              meta: {
                description: 'Upload NACH Form',
                template: 'linear',
                title: '5. Additional Details',
              },
              name: 'nach_form_component',
              progress: 100,
              status: 'executed',
              verification: null,
            },
          ],
          meta: {
            description: "Provide merchant's business information to start the POS journey",
            icon: 'Pricing Image',
            template: 'linear',
            title: '4. Payment Method & Service Selection',
          },
          name: 'pricing_step',
          progress: 100,
          status: 'executed',
        },
      ],
    },
  ],
});
