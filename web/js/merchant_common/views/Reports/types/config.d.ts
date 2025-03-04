export interface BaseConfigType {
  id: string;
  name: string;
  type: string;
  type_title?: string;
  description: string;
  scheduled?: boolean;
  consumer?: string;
  report_type?: string;
  template?: {
    referred_accounts?: string;
    file_meta?: {
      header?: boolean;
      filename?: string;
      password?: string;
      delimiter?: string;
      extension?: string;
      compression_format?: string;
      disable_quotes?: boolean;
    };
    external_platform?: string;
  };
  sftp_job_name?: string;
  pipeline_params?: {
    file_push?: {
      channel?: string;
      job_name?: string;
      protocol?: string;
    };
  };
  emails?: string[];
  created_by?: string;
  status?: null | undefined;
  source?: string;
  created_at?: number;
  updated_at?: number;
  feature_names?: string[];
  query_meta?: {
    joins?: string[];
    skews?: string[];
    tables?: string[];
    columns?: string[];
    filters?: string[];
    selects?: string[];
    order_by?: string[];
    joinConds?: string[];
  };
}
