export const listTransactionsResponse = {
  status_code: 200,
  success: true,
  data: {
    totals: null,
    total_count: '1',
    entities: {
      transactions: [
        {
          account_id: 'iacc_MSQShu0g115l39',
          balance: 9900,
          contact: '',
          created_at: 1692557031,
          credit: 0,
          currency: 'INR',
          debit: 100,
          instrument_id: 'igcard_MSQShu6AKOTMdN',
          instrument_type: 'giftcard',
          lob: null,
          merchant_id: 'JCTRhsU4aiY0tc',
          merchant_name: 'Athul Tuttu',
          pool_account_type: '',
          reference_id: 'MSQSumkq41ae6B',
          settlement_id: null,
          transaction_id: 'itxn_MSQSunez0tjxDX',
          transaction_reference_id: 'ipay_MSQSumyGI3Hl1j',
          transaction_type: 'payment',
          updated_at: 1692557031,
          user_id: 'iuser_MSQRPA3c8InCde',
        },
      ],
    },
  },
};
