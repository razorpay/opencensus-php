/**
 * Method to get eventCategory for Analytics based on the given pathname.
 * It processes the pathname and returns the corresponding event category.
 *
 * @param {string} pathname - The pathname from the URL, e.g., "/plans/".
 * @returns {string} - The corresponding event category for the given pathname.
 *
 * @example
 * const category = getEventCategoryFromPath('/payments');
 * console.log(category); // Output: 'Dashboard - Payments'
 */
export const getEventCategoryFromPath = (pathname: string): string => {
  // Remove slashes from path. Eg: /plans/ => plans
  pathname = pathname ? pathname.split('/').join('') : '';

  switch (pathname) {
    case 'payments':
      return 'Dashboard - Payments';
    case 'refunds':
      return 'Dashboard - Refunds';
    case 'paymentlinks':
      return 'Dashboard - Payment Links';
    case 'orders':
      return 'Dashboard - Orders';
    case 'settlements':
      return 'Dashboard - Settlements';
    case 'invoices':
    case 'items':
      return 'Dashboard - Invoices';
    case 'plans':
    case 'subscriptions':
      return 'Dashboard - Subscriptions';
    case 'virtualaccounts':
      return 'Dashboard - Smart Collect';
    default:
      return 'Dashboard - Home';
  }
};
