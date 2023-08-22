export type Config = {
  label: string;
  is_mandatory: boolean;
  masking_length: string;
  type: string;
  suffix?: string;
};

export type Payload = {
  configurations: Array<{
    should_be_deleted: boolean;
    configuration: Config;
  }>;
};
