const makeUpdatedReportConfigPayload = ({
  selectedColumnForReport,
  previousReportConfigData,
  reportName,
}) => {
  const updatedReportConfig = {
    ...previousReportConfigData,
    report_file_configs: {
      frontend_cols:
        Array.isArray(selectedColumnForReport) && selectedColumnForReport.length > 0
          ? selectedColumnForReport
              .map((column) => column.editedName || column.name)
              .filter(Boolean)
          : [],
      source_reporting_configs:
        Array.isArray(selectedColumnForReport) && selectedColumnForReport.length > 0
          ? Object.values(
              selectedColumnForReport.reduce((acc, column) => {
                const key = `${column.merchant_source_id || ''}${column.merchant_process_id || ''}`;
                if (!acc[key]) {
                  acc[key] = {
                    merchant_source_id: column.merchant_source_id || '',
                    merchant_process_id: column.merchant_process_id || '',
                    merchant_process_name: column.merchant_process_name || '',
                    merchant_source_name: column.merchant_source_name || '',
                    column_mapping: [],
                  };
                }
                acc[key].column_mapping.push({
                  id: column.id,
                  source_column: column.name || '',
                  report_column: column.editedName || column.name,
                });
                return acc;
              }, {}),
            )
          : [],
    },
    name: reportName,
  };

  return updatedReportConfig;
};

const makeReportConfigPayload = ({
  selectedProcessesItem,
  selectedColumnForReport,
  joiningConfig,
  reportName,
}) => {
  const reportConfigPayload = {
    name: reportName,
    merchant_processes: Array.isArray(selectedProcessesItem)
      ? selectedProcessesItem.map((process) => {
          return {
            merchant_process_id: process.id,
            merchant_source_ids: process.merchant_sources.map((source) => source.id),
          };
        })
      : [],
    joining_config:
      Array.isArray(selectedProcessesItem) && selectedProcessesItem.length >= 2
        ? joiningConfig
        : [],
    report_file_configs: {
      frontend_cols:
        Array.isArray(selectedColumnForReport) && selectedColumnForReport.length > 0
          ? selectedColumnForReport
              .map((column) => column.editedName || column.name)
              .filter(Boolean)
          : [],
      source_reporting_configs:
        Array.isArray(selectedColumnForReport) && selectedColumnForReport.length > 0
          ? Object.values(
              selectedColumnForReport.reduce((acc, column) => {
                const key = `${column.merchant_source_id || ''}${column.merchant_process_id || ''}`;
                if (!acc[key]) {
                  acc[key] = {
                    merchant_source_id: column.merchant_source_id || '',
                    merchant_process_id: column.merchant_process_id || '',
                    merchant_process_name: column.merchant_process_name || '',
                    merchant_source_name: column.merchant_source_name || '',
                    column_mapping: [],
                  };
                }
                acc[key].column_mapping.push({
                  id: column.id,
                  source_column: column.name || '',
                  report_column: column.editedName || column.name,
                });
                return acc;
              }, {}),
            )
          : [],
    },
  };

  return reportConfigPayload;
};

export { makeUpdatedReportConfigPayload, makeReportConfigPayload };
