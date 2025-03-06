import create from 'zustand';
import {
  AvailableColumnsType,
  TableNode,
  BaseReportType,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/types';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { formErrorMessages } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';

type state = {
  standardReportName: string;
  reportName: string;
  reportDescription: string;
  availableColumns: AvailableColumnsType;
  selectedColumns: Record<string, string[]>;
  isReportTypeChanged: boolean;
  errorInSections: Record<string, string>;
  displayColumns: TableNode<string>[];
  renamedColumns: Record<string, string>;
  standardConfigs: BaseConfigType[];
  userConfigs: BaseConfigType[];
  baseReport: BaseReportType;
  configsFetchTrigger: boolean;
  selectedConfigId: string;
};

type Action = {
  updateBaseReport: (baseReport: BaseReportType) => void;
  updateStandardReportName: (standardReportName: string) => void;
  updateReportName: (reportName: string) => void;
  updateReportDescription: (reportDescription: string) => void;
  setAvailableColumns: (availableColumns: AvailableColumnsType) => void;
  setSelectedColumns: (name: string, values: string[]) => void;
  resetSelectedFields: () => void;
  setIsReportTypeChanged: (isReportType: boolean) => void;
  validateProgressFormInView: () => void;
  deleteSelectedColumn: (id: string, value: string) => void;
  setRearrangeColumns: (updatedDisplayColumns: TableNode<string>[]) => void;
  setRenamedColumns: (columnKey: string, updatedName: string) => void;
  setStandardConfigs: (standardConfigs: BaseConfigType[]) => void;
  setUserConfigs: (userConfigs: BaseConfigType[]) => void;
  setConfigsFetchTrigger: () => void;
  setSelectedConfigId: (id: string) => void;
  setPreSelectedColumns: (
    renamedColumnMapping: Record<string, string[]>,
    selectedFields: Record<string, string[]>,
  ) => void;
};

export const useCreateConfigModal = create<state & Action>((set) => ({
  selectedConfigId: '',
  configsFetchTrigger: false,
  baseReport: {
    name: '',
    type: '',
    type_title: '',
    source: '',
    fields_map: {},
  },
  userConfigs: [],
  standardConfigs: [],
  reportConfigs: [],
  renamedColumns: {},
  displayColumns: [],
  errorInSections: {
    reportType: '',
    reportName: '',
    reportDescription: '',
  },
  isReportTypeWritten: 'none',
  isReportTypeChanged: false,
  standardReportName: '',
  reportName: '',
  reportDescription: '',
  availableColumns: { fields: {}, filters: {} },
  selectedColumns: {},
  updateBaseReport: (baseReport) => set(() => ({ baseReport })),
  updateStandardReportName: (standardReportName) => set(() => ({ standardReportName })),
  updateReportName: (reportName) => set(() => ({ reportName })),
  setStandardConfigs: (standardConfigs) => set(() => ({ standardConfigs })),
  setUserConfigs: (userConfigs) => set(() => ({ userConfigs })),
  updateReportDescription: (reportDescription) => set(() => ({ reportDescription })),
  setAvailableColumns: (availableColumns) => set(() => ({ availableColumns })),
  setSelectedColumns: (name, values) =>
    //Updates the selected columns and synchronizes related state properties.
    set((state) => {
      const updatedSelectedColumns = { ...state.selectedColumns };
      const renamedColumns: Record<string, string> = { ...state.renamedColumns };
      let updatedRenamedColumns = {};
      if (values.length === 0) {
        delete updatedSelectedColumns[name];
      } else {
        updatedSelectedColumns[name] = values;
      }
      let updatedDisplayColumns: TableNode<string>[] = [];
      let index = 0;
      Object.keys(updatedSelectedColumns).forEach((key) => {
        updatedSelectedColumns[key].forEach((value) => {
          updatedDisplayColumns.push({
            id: `${key}.${value}`,
            value: `${key}.${value}`,
          });
          index++;
        });
      });
      updatedDisplayColumns.forEach((col) => {
        updatedRenamedColumns[col.id] = renamedColumns[col.id] ?? col.value;
      });
      return {
        selectedColumns: updatedSelectedColumns,
        displayColumns: updatedDisplayColumns,
        renamedColumns: updatedRenamedColumns,
      };
    }),
  setPreSelectedColumns: (renamedColumnMapping, selectedFields) => {
    Object.keys(renamedColumnMapping).forEach((key) => {
      renamedColumnMapping[key].forEach((value) => {});
    });

    // Sets the pre-selected columns in the state.
    let preSelectedDisplayColumns: TableNode<string>[] = [];
    let preSelectedRenamedColumns: Record<string, string> = {};
    Object.keys(renamedColumnMapping).forEach((key) => {
      renamedColumnMapping[key].forEach((value) => {
        preSelectedDisplayColumns.push({
          id: value,
          value: value,
        });
      });
    });

    //sets the renamed columns
    Object.keys(renamedColumnMapping).forEach((key) => {
      renamedColumnMapping[key].forEach((value) => {
        preSelectedRenamedColumns[value] = key;
      });
    });

    set(() => {
      return {
        selectedColumns: selectedFields,
        displayColumns: preSelectedDisplayColumns,
        renamedColumns: preSelectedRenamedColumns,
      };
    });
  },
  resetSelectedFields: () =>
    set(() => ({
      selectedColumns: {},
      renamedColumns: {},
      displayColumns: [],
      errorInSections: {},
    })),
  setIsReportTypeChanged: (isReportTypeChanged) => set(() => ({ isReportTypeChanged })),
  setSelectedConfigId: (id) => set(() => ({ selectedConfigId: id })),
  validateProgressFormInView: () =>
    // Validates the progress form and updates error states.
    set((state) => {
      const updatedErrors = { ...state.errorInSections };

      if (state.standardReportName === '') {
        updatedErrors['reportType'] = formErrorMessages['baseReportTypeErrorText'];
      } else if (updatedErrors['reportType']) {
        delete updatedErrors['reportType'];
      }

      if (state.reportName === '') {
        updatedErrors['reportName'] = formErrorMessages['reportNameErrorText'];
      } else if (updatedErrors['reportName']) {
        delete updatedErrors['reportName'];
      }

      if (state.reportDescription === '') {
        updatedErrors['reportDescription'] = formErrorMessages['reportDescriptionErrorText'];
      } else if (updatedErrors['reportDescription']) {
        delete updatedErrors['reportDescription'];
      }

      return {
        errorInSections: updatedErrors,
      };
    }),
  deleteSelectedColumn: (id, col) =>
    //Deletes a selected column from the state.
    set((state) => {
      const [name, value] = col.split('.');
      const updatedSelectedColumns = { ...state.selectedColumns };
      let updatedDisplayColumns = [...state.displayColumns];
      let updatedRenamedColumns = { ...state.renamedColumns };
      updatedSelectedColumns[name] = updatedSelectedColumns[name].filter((item) => item !== value);
      updatedDisplayColumns = updatedDisplayColumns.filter((item) => item.value !== col);
      delete updatedRenamedColumns[id];
      if (updatedSelectedColumns[name].length === 0) {
        delete updatedSelectedColumns[name];
      }
      return {
        selectedColumns: updatedSelectedColumns,
        displayColumns: updatedDisplayColumns,
        renamedColumns: updatedRenamedColumns,
      };
    }),
  setRearrangeColumns: (updatedDisplayColumns) =>
    // Updates the order of displayed columns.
    set(() => {
      return {
        displayColumns: updatedDisplayColumns,
      };
    }),
  setRenamedColumns: (columnKey, updatedName) =>
    // Updates the renamed columns mapping.
    set((state) => {
      return {
        renamedColumns: {
          ...state.renamedColumns,
          [columnKey]: updatedName,
        },
      };
    }),
  setConfigsFetchTrigger: () =>
    //Toggles the `configsFetchTrigger` state to trigger an API call for fetching configurations
    set((state) => ({ configsFetchTrigger: !state.configsFetchTrigger })),
}));
