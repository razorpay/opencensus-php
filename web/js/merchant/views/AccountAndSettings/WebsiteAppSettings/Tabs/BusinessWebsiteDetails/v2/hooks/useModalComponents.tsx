import { useMemo } from 'react';
import {
  ModalHeader as BladeModalHeader,
  ModalBody as BladeModalBody,
  ModalFooter as BladeModalFooter,
  Modal as BladeModal,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
} from '@razorpay/blade/components';

interface ModalComponents {
  Modal: React.ComponentType<any>;
  ModalHeader: React.ComponentType<any>;
  ModalBody: React.ComponentType<any>;
  ModalFooter: React.ComponentType<any>;
}

const useModalComponents = (isMobile: boolean): ModalComponents => {
  return useMemo(() => {
    if (isMobile) {
      return {
        Modal: BottomSheet,
        ModalHeader: BottomSheetHeader,
        ModalBody: BottomSheetBody,
        ModalFooter: BottomSheetFooter,
      };
    } else {
      return {
        Modal: BladeModal,
        ModalHeader: BladeModalHeader,
        ModalBody: BladeModalBody,
        ModalFooter: BladeModalFooter,
      };
    }
  }, [isMobile]);
};

export default useModalComponents;
