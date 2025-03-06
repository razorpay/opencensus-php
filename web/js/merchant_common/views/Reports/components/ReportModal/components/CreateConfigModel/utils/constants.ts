export const totalProgressSteps: number = 3;

export const adminGeneratedReportIds: string[] = ['100000Razorpay'];

export const customReportsLimit: number = 15;

export const tableColumnHeaders: Record<string, string> = {
  selectedColumns: 'Selected Columns',
  columnName: 'Column Name',
  removeColumn: 'Remove Column',
};

export const headerTitles = {
  selectedColumns: 'SELECTED COLUMNS',
  availableColumns: 'AVAILABLE COLUMNS',
};

export const maxCustomReportLimitReached: string =
  'You have reached the maximum limit of custom reports allowed. Please delete an existing custom report to create a new report.';

export const errorMessages: string[] = [
  'The given report name is already in use. Please use a different name',
  'The name format is invalid.',
];

export const formErrorMessages: Record<string, string> = {
  reportNameErrorText: 'Report Name is required',
  baseReportTypeErrorText: 'Base Report Type is required',
  renamedColumnErrorText: 'Cannot Be Empty',
  reportDescriptionErrorText: 'Report Description is required',
};

export const modalTitles: Record<string, string> = {
  close: 'Discard Changes',
  delete: 'Delete Report',
  create: 'Create Custom Report',
  edit: 'Edit Report',
  clone: 'Clone Report',
};

export const modalDescriptions: Record<string, string> = {
  deleteConfig: 'Are you sure you want to delete this Report?',
  firstPage: 'Report Description',
  secondPage: 'Column selection and arrangement',
  thirdPage: 'Column names',
  closeModal: 'Are you sure you want to discard the entire progress?',
};

export const alertMessages: Record<string, string> = {
  emptyColumnNameError: 'Please fill all the column names',
  duplicateColumnNameError: 'Each column in the report must have a unique column name',
  reportCreationSuccess: 'Your Custom Report has been created successfully',
  reportCloneSuccess: 'Your Custom Report has been cloned successfully',
  reportEditionSuccess: 'Your Custom Report has been edited successfully',
  reportCreationFailure: 'Failed to create your custom report. Please try again later.',
  noColumnSelectedError: 'At least one column must be selected before proceeding!',
  somethingWentWrongError: 'Something went wrong please try again later!',
  invalidColumnNameError: 'Column name must start with an alphabet',
  columnSelectionMissingError:
    'At least one column must be selected before proceeding. Click on go back to select columns',
};

export const deviceRestrictionMessages = {
  Custom: 'Please login on dashboard via desktop to create a custom report.',
  Edit: 'Please login on dashboard via desktop to edit a custom report.',
  Clone: 'Please login on dashboard via desktop to clone and create a custom report.',
};
