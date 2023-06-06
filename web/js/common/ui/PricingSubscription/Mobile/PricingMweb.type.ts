import type {
  TrackingObjectType,
  PlansType,
} from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';
import { RouteComponentProps } from 'react-router-dom';

interface PaymentCheckoutMweb {
  plans: PlansType;
  trackInstrumentation: (type: string, trackingObject: TrackingObjectType) => void;
  togglePlan: string;
  setLoading: (value: React.SetStateAction<boolean>) => void;
  setSelectedPlanId: (value: React.SetStateAction<string>) => void;
  handlePaymentSuccess: (response: { razorpay_payment_id?: string }, plans: PlansType) => void;
  handlePaymentFailure: (
    response: { error?: { code: string; metadata?: { payment_id: string } } },
    plans: PlansType,
  ) => void;
  showNotificationToast: ({
    type,
    message,
    closeTimeout,
  }: {
    type: string;
    message: () => JSX.Element;
    closeTimeout?: number | undefined;
  }) => void;
}
interface PricingSectionMwebProps {
  plans: PlansType;
  toggleViewMore: () => void;
  isViewMore: boolean;
  togglePlan: string;
  featureIdToFeatureCopyMap: Record<string, string>;
  handleCheckoutPayment: (
    props: PaymentCheckoutMweb,
  ) => (plans?: PlansType | React.MouseEvent<HTMLButtonElement, MouseEvent>) => Promise<void>;
  closeBottomSheet: (value: boolean) => void;
  showNotificationToast: (object: {
    type: string;
    message: () => JSX.Element;
    closeTimeout?: number | undefined;
  }) => void;
  history: RouteComponentProps['history'];
  user: {
    current: string;
    isAllowedMultiple: (string) => boolean;
    isAccountAndSettingsRevampEnabled: boolean;
  };
  trackInstrumentation: (type: string, trackingObject: TrackingObjectType) => void;
}
export { PricingSectionMwebProps };
