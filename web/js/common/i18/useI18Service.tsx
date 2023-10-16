import { I18ServiceContext } from './I18ServiceProvider';
import { useContext } from 'react';

export const useI18Service = () => useContext(I18ServiceContext);
