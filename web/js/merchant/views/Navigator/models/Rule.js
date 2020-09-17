import { Operand } from './Operand';

export class RuleModel {
  name = '';
  rules = [];
  description = '';
  created_by = '';
  updated_by = '';
  outcome_type = '';
  strategy = 'default';
  mandatory_attributes = [];
  additional_attributes = [{ name: 'rule_mode', type: 'string', values: [''] }];
  precondition = {
    operands: [new Operand(), new Operand()],
    value: null,
    type: null,
  };
}
