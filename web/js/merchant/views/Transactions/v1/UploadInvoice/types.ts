export type UploadInvoiceInitialState = {
  isUploading: boolean;
  error: null | string;
  invoiceUploading: {
    [x: string]: boolean;
  };
  invoiceFetching: {
    [x: string]: boolean;
  };
};

export type UploadInvoiceReducerActionType = {
  type: string;
  payload: {
    id?: string;
  };
};

export type Params<V = string | number | boolean | null | undefined> = Record<string, V>;

type ReduxActionType = (payload: UploadInvoiceReducerActionType['payload']) => {
  type: string;
  payload: UploadInvoiceReducerActionType['payload'];
};

export type PaymentsListContainerProps = UploadInvoiceInitialState & {
  uploadInvoicePending: ReduxActionType;
  uploadInvoiceSuccess: ReduxActionType;
  uploadInvoiceError: ReduxActionType;
  viewInvoicePending: ReduxActionType;
  viewInvoiceSuccess: ReduxActionType;
  viewInvoiceError: ReduxActionType;
  showNotification: (arg: { type: string; message: string }) => void;
};

export type PaymentItem = {
  id?: string;
  status?: string;
  method?: string;
  notes?: {
    invoice_number?: string;
  };
  opgsp_invoice_doc?: string;
  opgsp_awb_doc?: string;
};

export type TrackAnalyticsType = {
  properties?: TrackAnalyticsProperties;
  objectName?: string;
  actionName: string;
};

export type PropertyType = string | number | boolean | null | undefined;

export type TrackAnalyticsProperties = {
  paymentId?: PropertyType;
  documentId?: PropertyType;
  paymentStatus?: PropertyType;
  emailFilled?: PropertyType;
  notesFilled?: PropertyType;
  count?: PropertyType;
  resultsReturned?: PropertyType;
  status?: 'success' | 'failure';
  version?: string;
};
