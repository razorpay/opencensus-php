const updateConfigData = {
  selectedColumnForReport: [
    {
      id: 'P8r5VefclLbgaE-P8qtK74NgvzHJx-date',
      merchant_source_id: 'P8qtK74NgvzHJx',
      merchant_process_id: 'P8r5VefclLbgaE',
      name: 'date',
      merchant_process_name: 'Test Bank Zomato Recon',
      merchant_source_name: 'TestBankFile',
      editedName: 'date',
    },
    {
      id: 'P8ulghazA6hcy8-P8sjlTEUUx0aH4-cash_id',
      merchant_source_id: 'P8sjlTEUUx0aH4',
      merchant_process_id: 'P8ulghazA6hcy8',
      name: 'cash_id',
      merchant_process_name: 'Test Cash Recon',
      merchant_source_name: 'TestCMSFile',
      editedName: 'cash_id',
    },
  ],
  previousReportConfigData: {
    id: 'PH61LqoaXFRz3m',
    merchant_id: 'LLkjLdJz4gWVvk',
    name: 'demo 4 Nov',
    config: {
      description: '',
      recon_report_config: {
        source_report_config: null,
        frontend_cols: null,
      },
    },
  },
  reportName: 'demo 4 Nov',
};

const updateConfigResult = {
  id: 'PH61LqoaXFRz3m',
  merchant_id: 'LLkjLdJz4gWVvk',
  name: 'demo 4 Nov',
  config: {
    description: '',
    recon_report_config: {
      source_report_config: null,
      frontend_cols: null,
    },
  },
  report_file_configs: {
    frontend_cols: ['date', 'cash_id'],
    source_reporting_configs: [
      {
        merchant_source_id: 'P8qtK74NgvzHJx',
        merchant_process_id: 'P8r5VefclLbgaE',
        merchant_process_name: 'Test Bank Zomato Recon',
        merchant_source_name: 'TestBankFile',
        column_mapping: [
          {
            id: 'P8r5VefclLbgaE-P8qtK74NgvzHJx-date',
            source_column: 'date',
            report_column: 'date',
          },
        ],
      },
      {
        merchant_source_id: 'P8sjlTEUUx0aH4',
        merchant_process_id: 'P8ulghazA6hcy8',
        merchant_process_name: 'Test Cash Recon',
        merchant_source_name: 'TestCMSFile',
        column_mapping: [
          {
            id: 'P8ulghazA6hcy8-P8sjlTEUUx0aH4-cash_id',
            source_column: 'cash_id',
            report_column: 'cash_id',
          },
        ],
      },
    ],
  },
};

const createReportConfig = {
  selectedProcessesItem: [
    {
      id: 'P8r5VefclLbgaE',
      name: 'Test Bank Zomato Recon',
      merchant_sources: [
        {
          id: 'P8qrobUYM7Eouw',
          name: 'TestPayoutFile',
        },
        {
          id: 'P8qtK74NgvzHJx',
          name: 'TestBankFile',
        },
      ],
    },
    {
      id: 'P8ulghazA6hcy8',
      name: 'Test Cash Recon',
      merchant_sources: [
        {
          id: 'P8sjlTEUUx0aH4',
          name: 'TestCMSFile',
        },
        {
          id: 'P8ulNS1lzPLJqC',
          name: 'TestCashPos',
        },
      ],
    },
  ],
  selectedColumnForReport: [
    {
      id: 'P8r5VefclLbgaE-P8qtK74NgvzHJx-date',
      merchant_source_id: 'P8qtK74NgvzHJx',
      merchant_process_id: 'P8r5VefclLbgaE',
      name: 'date',
      merchant_process_name: 'Test Bank Zomato Recon',
      merchant_source_name: 'TestBankFile',
      editedName: 'date',
    },
    {
      id: 'P8ulghazA6hcy8-P8sjlTEUUx0aH4-cash_id',
      merchant_source_id: 'P8sjlTEUUx0aH4',
      merchant_process_id: 'P8ulghazA6hcy8',
      name: 'cash_id',
      merchant_process_name: 'Test Cash Recon',
      merchant_source_name: 'TestCMSFile',
      editedName: 'cash_id',
    },
  ],
  joiningConfig: [
    {
      merchant_process_config: [
        {
          merchant_process_id: 'P8r5VefclLbgaE',
          joining_columns: [
            {
              column: 'date',
              merchant_source_id: 'P8qtK74NgvzHJx',
            },
          ],
        },
        {
          merchant_process_id: 'P8ulghazA6hcy8',
          joining_columns: [
            {
              column: 'collected_date',
              merchant_source_id: 'P8sjlTEUUx0aH4',
            },
          ],
        },
      ],
    },
  ],
  reportName: 'demo 4 Nov',
};

const createReportResult = {
  name: 'demo 4 Nov',
  merchant_processes: [
    {
      merchant_process_id: 'P8r5VefclLbgaE',
      merchant_source_ids: ['P8qrobUYM7Eouw', 'P8qtK74NgvzHJx'],
    },
    {
      merchant_process_id: 'P8ulghazA6hcy8',
      merchant_source_ids: ['P8sjlTEUUx0aH4', 'P8ulNS1lzPLJqC'],
    },
  ],
  joining_config: [
    {
      merchant_process_config: [
        {
          merchant_process_id: 'P8r5VefclLbgaE',
          joining_columns: [
            {
              column: 'date',
              merchant_source_id: 'P8qtK74NgvzHJx',
            },
          ],
        },
        {
          merchant_process_id: 'P8ulghazA6hcy8',
          joining_columns: [
            {
              column: 'collected_date',
              merchant_source_id: 'P8sjlTEUUx0aH4',
            },
          ],
        },
      ],
    },
  ],
  report_file_configs: {
    frontend_cols: ['date', 'cash_id'],
    source_reporting_configs: [
      {
        merchant_source_id: 'P8qtK74NgvzHJx',
        merchant_process_id: 'P8r5VefclLbgaE',
        merchant_process_name: 'Test Bank Zomato Recon',
        merchant_source_name: 'TestBankFile',
        column_mapping: [
          {
            id: 'P8r5VefclLbgaE-P8qtK74NgvzHJx-date',
            source_column: 'date',
            report_column: 'date',
          },
        ],
      },
      {
        merchant_source_id: 'P8sjlTEUUx0aH4',
        merchant_process_id: 'P8ulghazA6hcy8',
        merchant_process_name: 'Test Cash Recon',
        merchant_source_name: 'TestCMSFile',
        column_mapping: [
          {
            id: 'P8ulghazA6hcy8-P8sjlTEUUx0aH4-cash_id',
            source_column: 'cash_id',
            report_column: 'cash_id',
          },
        ],
      },
    ],
  },
};

const createReportConfigTwoProcess = {
  selectedProcessesItem: [
    {
      id: 'P8r5VefclLbgaE',
      name: 'Test Bank Zomato Recon',
      merchant_sources: [
        {
          id: 'P8qrobUYM7Eouw',
          name: 'TestPayoutFile',
        },
      ],
    },
  ],
  selectedColumnForReport: [],
  joiningConfig: [
    {
      merchant_process_config: [
        {
          merchant_process_id: 'P8r5VefclLbgaE',
          joining_columns: [
            {
              column: 'date',
              merchant_source_id: 'P8qtK74NgvzHJx',
            },
          ],
        },
      ],
    },
  ],
  reportName: 'Single Process Report',
};

const createReportConfigTwoProcessResult = {
  name: 'Single Process Report',
  merchant_processes: [
    {
      merchant_process_id: 'P8r5VefclLbgaE',
      merchant_source_ids: ['P8qrobUYM7Eouw'],
    },
  ],
  joining_config: [],
  report_file_configs: {
    frontend_cols: [],
    source_reporting_configs: [],
  },
};

export {
  updateConfigData,
  updateConfigResult,
  createReportConfig,
  createReportResult,
  createReportConfigTwoProcess,
  createReportConfigTwoProcessResult,
};
