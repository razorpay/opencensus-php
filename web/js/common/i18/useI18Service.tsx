import { useContext } from 'react';
import { I18ServiceContext } from 'shell/I18Context';

export const useI18Service = () => useContext(I18ServiceContext);
