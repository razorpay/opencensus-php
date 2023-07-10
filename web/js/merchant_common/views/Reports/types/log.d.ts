export interface FilterObjType {
  op: string;
  values: string[];
}

export interface TemplateOverridesType {
  file_meta?: {
    extension?: string;
    filename?: string;
  };
  filters?: {
    paymentlinksv2?: {
      mode?: FilterObjType;
    };
    payment_page_records?: {
      payment_link_id?: FilterObjType;
      batch_id?: FilterObjType;
      status?: FilterObjType;
    };
    payment_links?: {
      id?: FilterObjType;
    };
  };
}

export interface BaseLogType {
  id: string;
  consumer: string;
  config_id: string;
  file_id?: null | string;
  mode: string;
  status: string;
  generated_by: string;
  generated_at: null | number;
  emails: null | string[];
  send_email: boolean;
  template_overrides: null | TemplateOverridesType;
  start_time: number;
  end_time: number;
  schedule_id: null | string;
  created_at: number;
  updated_at: number;
  is_already_present: null | undefined;
  report_type: string;
  name: string;
  extension: string;
  all_emails: string[];
  batch_id: string;
}

export interface BaseLogPayloadType {
  config_id?: string;
  generated_by?: string;
  emails?: null | string[];
  template_overrides?: TemplateOverridesType;
  start_time: number;
  end_time: number;
}

export interface InternalLogType extends BaseLogType {
  polling: boolean;
}
