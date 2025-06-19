import {
  ActiveAccountData,
  BANK_DATA,
  PreviousAccountData,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/constants/data';
import {
  AccountData,
  AccountInfoPayloadInterface,
  BankDataInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import moment from 'moment';
import { getUser } from 'merchant/store';

const getDateFormat = (timestamp: number): string => {
  if (!timestamp) return '--';
  return moment.unix(timestamp).format('DD/MM/YYYY');
};

const getBankDetailPayload = ({
  type,
  details,
  isSettlementOnhold,
}: Omit<AccountInfoPayloadInterface, 'data'>): BankDataInterface[] => {
  const user = getUser();
  const bankDetails = BANK_DATA.reduce((accumulator, each) => {
    const { id, title } = each;
    if (!(id === 'ifsc' && (user.isCountrySingapore || user.isOrgCurlec)))
      accumulator.push({
        id,
        name: title,
        value: id === 'updated_at' ? getDateFormat(details[id]) : details[id],
      });
    return accumulator;
  }, [] as BankDataInterface[]);
  if (type === 'active') {
    bankDetails.push({
      id: 'settlements',
      name: 'Settlements',
      value: isSettlementOnhold ? 'ON HOLD' : 'ACTIVE',
      type: 'chip',
    });
  }
  return bankDetails;
};

export const getAccountData = ({
  type,
  data,
  isSettlementOnhold,
}: Omit<AccountInfoPayloadInterface, 'details'>): AccountData => {
  switch (type) {
    case 'previous':
      return {
        ...PreviousAccountData,
        banks: data.map((each) => getBankDetailPayload({ type, details: each })),
      };
    case 'active':
    default:
      return {
        ...ActiveAccountData,
        banks: data.map((each) =>
          getBankDetailPayload({ type, details: each, isSettlementOnhold }),
        ),
      };
  }
};
