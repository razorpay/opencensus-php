// Plotline Utils
export const getDaysSinceActivation = (date?: number): number => {
  try {
    if (!date) return 0;

    const currentDate = new Date();
    const activationDate = new Date(date * 1000);
    const timeDiff = Math.abs(currentDate.getTime() - activationDate.getTime());

    return Math.ceil(timeDiff / (1000 * 3600 * 24));
  } catch (error) {
    return 0;
  }
};

export const getValidityExpiryDate = (limit: number, activationTimestamp?: number): Date | null => {
  if (!activationTimestamp) return null;

  try {
    const activationDate = new Date(activationTimestamp * 1000);
    const expiryDate = new Date(activationDate);
    expiryDate.setDate(expiryDate.getDate() + limit);
    return expiryDate;
  } catch (error) {
    return null;
  }
};

// Rewards are assigned based on the number of transactions on 1st, 3rd and 5th transaction.
// This function returns the number of rewards based on the transaction count.
export const getRewardCount = (transactionCount: number | null) => {
  if (transactionCount === null) return 0;
  if (transactionCount < 3) return 1;
  if (transactionCount < 5) return 2;
  return 3;
};
