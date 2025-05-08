export type MerchantPaymentHandle = {
  paymentHandleSlug: string;
  title: string;
  url: string;
};

// Type definition for the payment handle query response
export type MerchantPaymentHandleResponseType = {
  merchantPaymentHandle: {
    code: number;
    message: string;
    paymentHandle?: MerchantPaymentHandle;
    success: boolean;
    __typename: string;
  };
};

// Type for payment handle suggestion response
export type MerchantPaymentHandleSuggestionResponseType = {
  merchantPaymentHandleSuggestions: {
    __typename: string;
    suggestions: string[];
  };
};

// Type for payment handle availability response
export type MerchantPaymentHandleAvailabilityResponseType = {
  merchantPaymentHandleAvailability: {
    __typename: string;
    code: number;
    message: string;
    success: boolean;
    isPaymentHandleAvailable?: boolean;
  };
};

// Type for encrypted amount mutation response
export type MerchantPaymentHandleEncryptedAmountResponseType = {
  merchantPaymentHandleEncryptedAmount: {
    __typename: string;
    code: number;
    message: string;
    success: boolean;
    encryptedAmount?: string;
  };
};

// Type for update payment handle mutation response
export type MerchantPaymentHandleUpdateResponseType = {
  merchantPaymentHandleUpdate: {
    __typename: string;
    code: number;
    message: string;
    success: boolean;
    paymentHandle?: MerchantPaymentHandle;
  };
};

// Type for money input used in encrypt amount mutation
export type MoneyInput = {
  value: number;
  currency: {
    code: string;
  };
};

// Type for the hook return value
export type UseMerchantPaymentHandleReturn = {
  // Payment handle data and states
  paymentHandleData: MerchantPaymentHandleResponseType | undefined;
  isPaymentHandleLoading: boolean;
  fetchPaymentHandle: () => void;

  // Handle Suggestion data
  fetchHandleSuggestions: (count?: number) => Promise<MerchantPaymentHandleSuggestionResponseType>;

  // Handle Availability data
  fetchHandleAvailability: (
    handle: string,
  ) => Promise<MerchantPaymentHandleAvailabilityResponseType>;

  // Mutations
  mutateEncryptedAmount: (params: {
    amount: string;
  }) => Promise<MerchantPaymentHandleEncryptedAmountResponseType>;
  updatePaymentHandle: (params: {
    handle: string;
  }) => Promise<MerchantPaymentHandleUpdateResponseType>;
};
