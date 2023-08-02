import { sanitizeTabName } from 'merchant/views/Settlements/v2/util';
import {
  CREDIT_ENTITY_COLUMNS,
  DEBIT_ENTITY_COLUMNS,
  DEFAULT_CREDIT_ENTITY_COLUMN,
  DEFAULT_DEBIT_ENTITY_COLUMN,
  mobileColumns,
} from './constants';

export const getEntityColumns = ({ tab, sectionType, isMobile }) => {
  let columns = [];
  if (sectionType === 'gross_settlements') {
    columns = CREDIT_ENTITY_COLUMNS[tab] || DEFAULT_CREDIT_ENTITY_COLUMN;
  } else if (sectionType === 'deductions') {
    columns = DEBIT_ENTITY_COLUMNS[tab] || DEFAULT_DEBIT_ENTITY_COLUMN;
  }
  if (isMobile) {
    columns = columns.filter((eachColumn) => mobileColumns.includes(eachColumn));
  }
  return columns;
};

// Function return new amount and net deduction based of section type (credit/debit)
export const getNetValue = ({ fee, amount, sectionType, activeTab }) => {
  const tab = sanitizeTabName(activeTab);
  let netValue = 0;
  if (sectionType === 'gross_settlements') {
    // this is net amount
    netValue = amount - fee;
  } else if (sectionType === 'deductions') {
    // this is net deduction
    if (tab === 'fund') {
      netValue = fee;
    } else {
      netValue = amount + fee;
    }
  }
  return netValue;
};
