import { useContext } from 'react';
import { I18ServiceContext } from '@federated/dashboards/payments/services/i18Service';

export const useI18Service = () => useContext(I18ServiceContext);
