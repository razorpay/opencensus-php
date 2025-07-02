import { convertToMinorUnit, convertToMajorUnit } from '@razorpay/i18nify-js/currency';

export const getCurrency = (ruleDetails) => {
  let currency = 'INR';
  if (ruleDetails?.precondition) {
    currency = findCurrency(ruleDetails?.precondition?.operands);
  }
  return currency;
};

// Helper function to find currency from operands
const findCurrency = (operands) => {
  let currency = 'INR'; // default currency
  
  if (!operands || !Array.isArray(operands)) return currency;
  
  for (const operand of operands) {
    if (operand.type === 'logical' && operand.operands) {
      const foundCurrency = findCurrency(operand.operands);
      if (foundCurrency !== 'INR') {
        currency = foundCurrency;
        break;
      }
    } else if (operand.type === 'comparator' && operand.operands) {
      const [left, right] = operand.operands;
      if (left?.value === '$payment.optimizer_currency' && right?.value) {
        currency = right.value;
        break;
      }
    }
  }
  
  return currency;
};

// Helper function to convert amount operands using a conversion function
const convertAmountOperands = (operands, currency, conversionFn) => {
  if (!operands || !Array.isArray(operands)) return;
  
  for (const operand of operands) {
    if (operand.type === 'logical' && operand.operands) {
      convertAmountOperands(operand.operands, currency, conversionFn);
    } else if (operand.type === 'comparator' && operand.operands) {
      const [left, right] = operand.operands;
      
      // Check if this is an amount comparison
      if (left?.value === '$payment.navigator_amount' && right?.value) {
        if (right.type === 'array') {
          // Handle "between" case with comma-separated values
          const amounts = right.value.split(',');
          const convertedAmounts = amounts.map(amount => 
            String(conversionFn(Number(amount.trim()), { currency: currency as any }))
          );
          right.value = convertedAmounts.join(',');
        } else {
          // Handle single amount value (equality case)
          const amount = Number(right.value);
          right.value = String(conversionFn(amount, { currency: currency as any }));
        }
      }
    }
  }
};

// Generic function for amount conversion
const convertAmounts = (data, conversionFn) => {
  // First find the currency
  const currency = data?.precondition?.operands ? 
    findCurrency(data.precondition.operands) : 'INR';

  // If currency is an array, return the data as is, no need to convert amount
  if (currency?.split(',').length > 1) {
    return data;
  }
  
  // Then convert amounts using the found currency
  if (data?.precondition?.operands) {
    convertAmountOperands(data.precondition.operands, currency, conversionFn);
  }
  
  return data;
};

export const amountConversionToMinorUnit = (data) => {
  return convertAmounts(data, convertToMinorUnit);
};

export const amountConversionToMajorUnit = (data) => {
  return convertAmounts(data, convertToMajorUnit);
};
