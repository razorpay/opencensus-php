export interface AccountType {
  name: string;
  id: string;
  email: string;
  code: string;
  current?: string;
}
export interface AccountStateType {
  loading: boolean;
  error: unknown;
  accounts: AccountType[];
  count: number;
}
