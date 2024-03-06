import { ShowNotificationType } from 'common/typings';
import { Location } from 'merchant/views/MagicCheckout/common/components/ZoneModal/types';

export interface ZonesUploadProps {
  closeModal: () => void;
  createZoneUpload: (arg: any) => Promise<any>;
  updateZoneUpload: (arg: any) => Promise<any>;
  showNotification: ShowNotificationType;
  isOpen: boolean;
  itemCategoryId?: string;
  zoneType: string;
  mode: string;
  zone?: Zone;
}

export interface Zone {
  id?: string;
  name?: string;
  type: string;
  itemCategoryId?: string;
  locations?: Location[];
  state_count?: number | string;
  shipping_methods?: any[];
}

export interface ZonePayload {
  id?: string;
  name?: string;
  type: string;
  itemCategoryId?: string;
  locations?: Location[];
  state_count?: number | string;
  shipping_methods?: any[];
  file: File;
  progressTracker: Record<string, any>;
}

export interface FileUploadResponse {
  success: boolean;
  data: any;
  errors?: any;
}
