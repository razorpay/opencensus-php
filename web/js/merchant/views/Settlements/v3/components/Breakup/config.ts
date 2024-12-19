import { User } from 'common/typings';
import { titleCase } from 'common/utils/rzp-utils';
import { getOrg } from 'merchant/store';
import {
  BreakupComponentInterface,
  BreakupDetailsInterface,
  BreakUpDetailsResponse,
  BreakupItems,
} from 'merchant/views/Settlements/v3/typings';

// TODO: update this data after getting value from BE.
const instrumentMapper = (user: User) => ({
  payment_domestic: {
    displayName: 'Payment',
    tooltipInfo:
      'Amount collected from customers for products or services purchased or availed on your website or app',
  },
  tax: {
    displayName: 'Tax',
    tooltipInfo: user.isOrgCurlec ? 'Sales and Service Tax (SST)' : 'Goods and Service Tax (GST)',
  },
  fee: {
    displayName: 'Fee',
    tooltipInfo: `Platform fees charged by ${getOrg()?.business_name ?? 'Razorpay'}`,
  },
  dispute: {
    displayName: 'Disputes',
    tooltipInfo:
      'Amount adjusted for customer disputes (like unauthorised charges or failure to deliver the promised merchandise)',
  },
  transfer: {
    displayName: 'Transfers',
    tooltipInfo: 'Amount transferred to your linked account(s)',
  },
  transfer_international: {
    displayName: 'Transfers',
    tooltipInfo: 'Amount transferred to your linked account(s)',
  },
  settlement_transfer: {
    displayName: 'Transfers',
    tooltipInfo: 'Amount transferred to your linked account(s)',
  },
  refund: {
    displayName: 'Refunds',
    tooltipInfo: 'Amount reversed to customer(s) bank account',
  },
  refund_domestic: {
    displayName: 'Refunds',
    tooltipInfo: 'Amount reversed to customer(s) bank account',
  },
  refund_credits: {
    displayName: 'Refunds',
    tooltipInfo: 'Amount reversed to customer(s) bank account',
  },
  refund_international: {
    displayName: 'Refunds',
    tooltipInfo: 'Amount reversed to customer(s) bank account',
  },
  payment: {
    displayName: 'Payment',
    tooltipInfo:
      'Amount collected from customers for products or services purchased or availed on your website or app',
  },
  payment_international: {
    displayName: 'Payment',
    tooltipInfo:
      'Amount collected from customers for products or services purchased or availed on your website or app',
  },
  reversal: {
    displayName: 'Reversals',
    tooltipInfo: 'Amount transferred to your bank account from linked account(s)',
  },
  fund_account_validation: {
    displayName: 'Funds',
    tooltipInfo:
      "Validation to ensure that your customer's fund account is the right account number",
  },
  adjustment: {
    displayName: 'Adjustments',
    tooltipInfo: 'Amount adjusted due to database inconsistencies or technical issues',
  },
  credit_repayment: {
    displayName: 'Credit repayment',
    tooltipInfo:
      'Amount deducted from the settlement as this amount was deposited in your bank account previously as a credit',
  },
  'settlement.ondemand': {
    displayName: 'On demand settlement',
    tooltipInfo:
      'Amount deducted from the settlement as this was deposited in your bank account previously as an on-demand settlement',
  },
});

const getBreakupComponentWise = ({
  item,
  user,
}: {
  item: Pick<BreakupItems, 'amount' | 'component'>;
  user: User;
}): BreakupComponentInterface => {
  return {
    id: item.component,
    name: instrumentMapper(user)[item.component]?.displayName || titleCase(item.component),
    amount: item.amount,
    tooltipInfo: instrumentMapper(user)[item.component]?.tooltipInfo,
  };
};

export const getBreakUpDetails = ({
  items,
  isBreakupNew,
  user,
}: Pick<BreakupDetailsInterface, 'items' | 'isBreakupNew'> & {
  user: User;
}): BreakUpDetailsResponse => {
  const deductions = {
    tax: 0,
    fee: 0,
  };
  let netSettlements = 0;
  return items.reduce(
    (accumulator, each, index) => {
      const entryItem: BreakupComponentInterface = getBreakupComponentWise({ item: each, user });
      if (each.type === 'credit' || each.type === 'debit') {
        if (each.type === 'credit') {
          netSettlements = netSettlements + each.amount;
          accumulator.grossSettlements.amount += each.amount;
        }
        if (each.type === 'debit') {
          netSettlements = netSettlements - each.amount;
          accumulator.deductions.amount += each.amount;
        }
        const { components, entries } =
          each.type === 'credit' ? accumulator.grossSettlements : accumulator.deductions;
        if (components.includes(entryItem.name)) {
          const componentPosition = entries.findIndex((each) => each.name === entryItem.name);
          if (componentPosition > -1) {
            entries[componentPosition].amount += entryItem.amount;
          }
        } else {
          components.push(entryItem.name);
          entries.push({ ...entryItem });
        }
      }

      if (isBreakupNew) {
        netSettlements = netSettlements - each.tax - each.fee;
        accumulator.deductions.amount += each.tax + each.fee;
        deductions.tax = deductions.tax + each.tax;
        deductions.fee = deductions.fee + each.fee;
      }
      if (index === items.length - 1) {
        if (isBreakupNew) {
          Object.keys(deductions).forEach((each) => {
            accumulator.deductions.entries.push(
              getBreakupComponentWise({
                item: { component: each, amount: deductions[each] },
                user,
              }),
            );
          });
        }
        accumulator.netSettlements.amount = netSettlements;
      }
      return accumulator;
    },
    {
      grossSettlements: {
        amount: 0,
        entries: [],
        components: [],
      },
      deductions: {
        amount: 0,
        entries: [],
        components: [],
      },
      netSettlements: {
        amount: 0,
      },
    } as BreakUpDetailsResponse,
  );
};
