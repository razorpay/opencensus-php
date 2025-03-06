import { fetchReportData, getConfigById } from 'merchant_common/views/Reports/api/createConfigs';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import {
  AvailableColumnsType,
  CustomReportType,
  HandleValidationType,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';
import { showNotification } from 'merchant_common/reducers/notifications';
import { alertMessages } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';
import { ResType } from 'merchant_common/views/Reports/api/types';

export const handleValidation = async ({
  progressFormInView,
  setProgressFormInView,
  setIsLoading,
}: HandleValidationType) => {
  const {
    standardReportName,
    setAvailableColumns,
    isReportTypeChanged,
    setIsReportTypeChanged,
    reportName,
    reportDescription,
    validateProgressFormInView,
    resetSelectedFields,
    selectedColumns,
    selectedConfigId,
    updateBaseReport,
    setPreSelectedColumns,
  } = useCreateConfigModal.getState();

  // Count the total number of selected columns
  const totalCount = Object.values(selectedColumns).reduce((acc, curr) => acc + curr.length, 0);

  const mapColumnsToRenamed = (
    fieldsMap: Record<string, string[]>,
    outputFields: string[],
  ): Record<string, string[]> => {
    const columnMappings: Record<string, string[]> = {};

    for (const field of outputFields) {
      if (fieldsMap[field]) {
        columnMappings[field] = fieldsMap[field];
      }
    }
    return columnMappings;
  };

  // Categorizing the columns based on the prefix
  const categorizeColumns = (fieldsMap: Record<string, string[]>): Record<string, string[]> => {
    const categorizedFields: Record<string, string[]> = {};
    for (const key in fieldsMap) {
      const value = fieldsMap[key][0];
      const [prefix, suffix] = value.split('.');
      if (!categorizedFields[prefix]) {
        categorizedFields[prefix] = [];
      }
      categorizedFields[prefix].push(suffix);
    }
    return categorizedFields;
  };

  // Fetching details of the selected configuration
  const fetchAndUpdateBaseReport = async (configId): Promise<ResType<CustomReportType>> => {
    return await getConfigById(configId ?? '');
  };

  // Fetching the available columns based on the report type
  const handleFetchReportData = async (type, source, progressFormInView): Promise<void> => {
    try {
      const res = await fetchReportData(type, source ?? '');
      setAvailableColumns(res.data as AvailableColumnsType);
      setIsReportTypeChanged(false);
      setProgressFormInView(progressFormInView + 1);
    } catch (error) {
      showNotification({
        type: 'error',
        message: alertMessages['somethingWentWrongError'],
      });
    } finally {
      setIsLoading(false);
    }
  };

  if (progressFormInView === 1) {
    validateProgressFormInView();

    if (standardReportName === '' || reportName === '' || reportDescription === '') {
      return;
    }

    if (isReportTypeChanged) {
      setIsLoading(true);
      resetSelectedFields();
      fetchAndUpdateBaseReport(selectedConfigId)
        .then(async (res) => {
          const updatedBaseReport = {
            name: res.data.name ?? '',
            type: res.data.type ?? '',
            type_title: res.data.type_title ?? '',
            source: res.data.source ?? '',
            fields_map: res.data.template?.fields_map ?? {},
            output_fields: res.data.template?.output_fields ?? [],
          };
          updateBaseReport(updatedBaseReport);
          if (
            !updatedBaseReport.fields_map ||
            Object.keys(updatedBaseReport.fields_map).length === 0 ||
            updatedBaseReport.source === '' ||
            updatedBaseReport.type === ''
          ) {
            setIsLoading(false);
            return;
          }
          const renamedColumnMapping = mapColumnsToRenamed(
            updatedBaseReport.fields_map,
            updatedBaseReport.output_fields,
          );

          const selectedFields = categorizeColumns(updatedBaseReport.fields_map);

          setPreSelectedColumns(renamedColumnMapping, selectedFields);
          await handleFetchReportData(
            updatedBaseReport.type,
            updatedBaseReport.source,
            progressFormInView,
          );
        })
        .catch((error) => {
          setIsLoading(false);
        });
    } else {
      setProgressFormInView(progressFormInView + 1);
    }
  } else if (progressFormInView === 2) {
    if (totalCount > 0) {
      setProgressFormInView(progressFormInView + 1);
    } else {
      showNotification({
        type: 'error',
        message: alertMessages['noColumnSelectedError'],
      });
    }
  }
};
