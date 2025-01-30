import type { TableData } from '@razorpay/blade/components';
import type {
  Rule as ACODRule,
  RuleType as ACODRuleType,
} from 'merchant/reducers/magicCheckout/magicxACODRules/types';

export type ACODTableProps = {
  type: ACODRuleType;
  rules: ACODRule[];
  ruleLimit: number;
};

export type TableRule<T extends ACODRuleType> = ACODRule<T> & {
  about: string;
};

export type ACODTableData<T extends ACODRuleType> = TableData<TableRule<T>>;
