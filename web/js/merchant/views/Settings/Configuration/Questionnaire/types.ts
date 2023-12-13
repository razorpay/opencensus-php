import { FormikContextType } from 'formik';

import { User } from 'common/typings';

export type FormikValues = {
  accepts_intl_txns: string;
  documents: Record<string, { display_name: string; id: string }[] | null>;
};

export type AdditionalDocumentsProps = {
  user: User;
  disabled?: boolean;
  saveFormData: (values: FormikContextType<FormikValues>, skipDirtyCheck?: boolean) => void;
  showNotification: (showNotification: { type: string; message: string }) => void;
};

export type Doc = {
  label: string;
  name: string;
  type: string;
  info?: string;
  tooltip?: string;
  isRequired?: boolean;
  subLabel?: string;
  options?: Doc[];
};

export type UseAdditionalDocumentsReturn = {
  docs: Doc[];
  selectedDocument: Doc[];
  selectedOption: string[];
  formikDocuments: Record<string, { display_name: string; id: string }[] | null>;
  handleSelect: (value: string | undefined, index: number) => void;
  handleUploadFile: (
    docType,
    file,
    progressTracker,
  ) => Promise<
    | void
    | {
        id: string;
        display_name: string;
      }
    | undefined
  >;
  handleFileRemove: (docId: string, docType: string) => void;
  filterOtherSelectOptions: (
    index: number,
    options: { label: string; name: string; tooltip?: string }[] | undefined,
  ) => { label: string; name: string; tooltip?: string }[];
};
