import React, { useRef, ReactNode } from 'react';
import Button from '@razorpay/blade/src/atoms/Button';
import Space from '@razorpay/blade/src/atoms/Space';
import { Motion, spring, presets } from 'react-motion';
import Layer from '../Layer/Layer';
import {
  DialogContainer,
  Dialog,
  BottomSheet,
  BottomSheetHandle,
  CloseIconContainer,
  BottomSheetTextHeader,
} from './Styled';

export interface ModalPropsT {
  onClose: () => void;
  isOpen: boolean;
  closeable?: boolean;
  children: ReactNode;
  bottomsheet?: boolean;
  bottomSheetHeight?: string;
  bottomSheetHeaderText?: string;
}

const Modal: React.FC<ModalPropsT> = ({
  isOpen,
  closeable = true,
  onClose,
  children,
  bottomsheet = false,
  bottomSheetHeight = 'inherit',
  bottomSheetHeaderText = '',
}) => {
  const dailogRef = useRef(null);
  const dailogContainerRef = useRef(null);
  const onEscape = () => {
    if (!closeable) {
      return;
    }
    onClose();
  };
  const onDocClick = (e: MouseEvent) => {
    if (!closeable) {
      return;
    }
    if (
      e.target &&
      e.target instanceof HTMLElement &&
      e.target.contains(dailogContainerRef.current)
    ) {
      onClose();
    }
  };

  if (!isOpen) {
    return null;
  }
  return (
    <Layer onEscape={onEscape} onDocClick={onDocClick}>
      <Motion
        defaultStyle={{ y: 20, opacity: 0, sheetY: 100 }}
        style={{
          y: spring(0, { ...presets.gentle, precision: 0.1 }),
          opacity: spring(1, { ...presets.gentle, precision: 0.1 }),
          sheetY: spring(0, { ...presets.gentle, precision: 0.1 }),
        }}
      >
        {(styles) => (
          <DialogContainer
            $bottomSheet={bottomsheet}
            $opacity={styles.opacity}
            ref={dailogContainerRef}
          >
            {bottomsheet ? (
              <BottomSheet $y={styles.sheetY} $bottomSheetHeight={bottomSheetHeight}>
                <BottomSheetHandle />
                {bottomSheetHeaderText ? (
                  <Space padding={[1.5, 0, 1, 0]} margin={[0, 3, 0, 3]}>
                    <BottomSheetTextHeader size="xsmall" color="shade.960" weight="bold">
                      {bottomSheetHeaderText}
                      {closeable ? (
                        <CloseIconContainer data-testid="modalCloseButton" onClick={onClose}>
                          <Button
                            variant="tertiary"
                            size="small"
                            variantColor="shade"
                            icon="close"
                          />
                        </CloseIconContainer>
                      ) : null}
                    </BottomSheetTextHeader>
                  </Space>
                ) : null}
                {children}
              </BottomSheet>
            ) : (
              <Dialog role="dialog" $opacity={styles.opacity} $y={styles.y} ref={dailogRef}>
                {children}
                {closeable ? (
                  <CloseIconContainer data-testid="modalCloseButton" onClick={onClose}>
                    <Button variant="tertiary" size="small" variantColor="shade" icon="close" />
                  </CloseIconContainer>
                ) : null}
              </Dialog>
            )}
          </DialogContainer>
        )}
      </Motion>
    </Layer>
  );
};

export default Modal;
