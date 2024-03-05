declare global {
  interface Window {
    rzp_user: any;
    rzp_org: any;
  }
}
/**
 * Default request data to be sent along with every experiment.
 */
export const defaultRequestData = {
  mid: window.rzp_user?.current ?? window.rzp_user?.id,
  org_id: window.rzp_org?.id,
  user_id: window.rzp_user?.user?.id,
};

/**
 *  To be consumed via requestData fn args.
 */
export const requestDataArgs = {
  partner_type: window.rzp_user?.partner_type as string,
};
