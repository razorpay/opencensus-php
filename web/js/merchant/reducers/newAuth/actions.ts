export const SET_MERCHANT_ID = 'SET_MERCHANT_ID';

interface SetMerchantIDAction {
  type: typeof SET_MERCHANT_ID;
  payload: any;
}

export type NewAuthAction = SetMerchantIDAction;

export const setMerchantID = (merchantID: string): SetMerchantIDAction => {
  return {
    type: SET_MERCHANT_ID,
    payload: merchantID,
  };
};
