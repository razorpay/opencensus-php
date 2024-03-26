export const PRODUCT = {
  header: 'Razorpay POS',
  description: 'Reconcile Razorpay POS, billing POS, bank MPR and bank Statements',
  reconTypes: {
    Bank_Recon: {
      header: 'Bank and Razorpay records',
      file_config: [
        { source_name: 'POS_Txn', master_source_id: '', show_upload: true },
        { source_name: 'POS_Refund', master_source_id: '', show_upload: true },
      ],
      master_process_id: '',
      description: 'Between your bank records and Razorpay records',
      is_recommended: false,
    },
  },
};

export const MERCHANT_META = {
  products: {
    [PRODUCT.header]: PRODUCT,
  },
};
