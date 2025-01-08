export type PurposeCodes = {
  codes: { purposeCode: string; description: string; helpText: string }[];
  groups: { purposeGroup: string; codes: PurposeCodes['codes'] }[];
};

export type ValidationState = {
  state: 'none' | 'error' | undefined;
  errorText: string;
};
