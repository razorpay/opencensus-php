interface BalanceItem {
  negative_limit_auto: number;
  negative_limit_manual: number;
}

interface BalanceConfig {
  loading: boolean;
  error: boolean;
  data: {
    items: BalanceItem[];
  };
}

/**
 * Determines whether a negative balance exceeds the allowed limit.
 *
 * @param {BalanceConfig} balanceConfig - The configuration containing balance data.
 * @param {number | undefined} balance - The current balance.
 * @returns {boolean} - Returns true if the balance exceeds the negative limit, false otherwise.
 */
export function handleNegativeBalanceLimit(balanceConfig: BalanceConfig, balance?: number): boolean {
  if (balance === undefined || balance >= 0) return false;

  if (
    balanceConfig.loading === true ||
    balanceConfig.error ||
    balanceConfig.data.items.length === 0
  ) {
    return false;
  }

  const { items } = balanceConfig.data;
  const { negative_limit_auto, negative_limit_manual } = items[0];

  const maxLimit = -Math.max(negative_limit_auto, negative_limit_manual);

  return balance <= maxLimit;
}
