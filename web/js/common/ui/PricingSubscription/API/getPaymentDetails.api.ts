import * as yup from 'yup';

import { merchantFetch } from 'merchant/utils/ajax';

import type { PlansType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';

export interface PlansDetails {
  name: string;
  amount: number;
  frequency: string;
  tax_percentage: number;
  icon_url: string;
}
export interface ReturnPaymentResponse extends PlansDetails {
  payment_methods: string[];
}
interface PaymentMode {
  data: {
    response: {
      payment_methods: string[];
      plan_details: PlansDetails;
    };
    status_code: number;
  };
}
const MULTI_PAYMENT_MODE = 'MULTI_PAYMENT_MODE';
const getPaymentOptionsResponseSchema = {
  [MULTI_PAYMENT_MODE]: yup.object().shape({
    data: yup
      .object()
      .required()
      .shape({
        response: yup
          .object()
          .required()
          .shape({
            payment_methods: yup.array().required().strict(true),
            plan_details: yup
              .object()
              .required()
              .shape({
                name: yup.string().required().strict(true),
                amount: yup.string().required().strict(true),
                frequency: yup.string().required().strict(true),
                tax_percentage: yup.string().required().strict(true),
                icon_url: yup.string().required().strict(true),
              }),
          }),
        status_code: yup.number().required().strict(true),
      }),
  }),
};
export const getPaymentOptions = async ({
  plans,
  togglePlan,
}: {
  plans: PlansType;
  togglePlan: string;
}): Promise<ReturnPaymentResponse | Record<string, unknown>> => {
  const paymentMethod = await merchantFetch({
    method: 'post',
    url: `pricing/merchant/subscriptions/getPaymentDetails?plan_id=${plans.id}&frequency=${togglePlan}`,
    mode: 'live',
    data: {},
  });
  const isPaymentMethod =
    getPaymentOptionsResponseSchema[MULTI_PAYMENT_MODE].isValidSync(paymentMethod);

  if (isPaymentMethod) {
    const {
      data: { response, status_code },
    } = paymentMethod as PaymentMode;
    if (status_code === 200) {
      const {
        payment_methods,
        plan_details: { name, amount, frequency, tax_percentage, icon_url },
      } = response;

      return {
        payment_methods,
        name,
        amount,
        frequency,
        tax_percentage,
        icon_url,
      };
    } else {
      throw new Error(`Something went wrong. Please try again. status code: ${status_code}`);
    }
  } else {
    throw new Error('Payment method validation failed');
  }
};
