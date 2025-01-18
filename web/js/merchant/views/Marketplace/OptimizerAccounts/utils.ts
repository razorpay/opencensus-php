import { Provider } from 'merchant/views/Optimizer/types';
import { OptimizerAccount } from './types';

export const getAccountName = (optimizerAccounts: OptimizerAccount[], accountId: string) => {
  return optimizerAccounts.filter((account) => account.id === accountId)[0]?.account_name ?? '';
};

export const getProviderName = (providers: Provider[], id: string) => {
  return providers?.filter((provider) => provider.Terminal_id === id)[0]?.Provider_name ?? '';
};
