export type PaymentDownloadSwiftCopyProps = {
  paymentId: string;
  notify: (message: { type: string; message: string }) => void;
  asIcon: boolean;
};

export type DownloadSwiftCopy = {
  title: string;
  value: (item: { id: string }) => JSX.Element;
};

export type TrackAnalyticsType = {
  properties?: Record<string, string>;
  objectName?: string;
  actionName: string;
};
