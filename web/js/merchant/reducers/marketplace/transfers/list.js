const TRANSFER_EDIT = 'TRANSFER_EDIT';

export const updateTranferInList = (transfer) => {
  return {
    type: `${TRANSFER_EDIT}::SUCCESS`,
    payload: transfer,
  };
};
