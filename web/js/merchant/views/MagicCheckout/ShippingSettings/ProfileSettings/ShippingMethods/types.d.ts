import {
  ShippingEngineStore,
  ShippingMethod,
  Zone,
} from 'merchant/reducers/magicCheckout/shippingEngine/types';

export interface ShippingMethodsModalProps {
  isOpen: boolean;
  closeModal: () => void;
  isLoading: boolean;
  disableConfirmButton?: boolean;
  shippingEngine: ShippingEngineStore;
  method?: ShippingMethod;
  zone: Zone;
  mode: string;
  createShippingMethod: (method: unknown) => Promise<void>;
  updateShippingMethod: (method: unknown) => Promise<void>;
  showNotification: (payload: Record<string, unknown>) => void;
}

export type ShippingFeeRule = {
  amount?: {
    gte: number;
    lt: number;
  };
  weight?: {
    gte: number;
    lt: number;
  };
};
