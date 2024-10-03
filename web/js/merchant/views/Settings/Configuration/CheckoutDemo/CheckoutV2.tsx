import React, { useEffect, useRef, useMemo } from 'react';
import debounce from 'lodash/debounce';

import { toBase64 } from 'merchant/views/PartnerDashboard/SubMerchant/components/utils';
import { CheckoutFrame } from 'merchant/views/Settings/Configuration/CheckoutDemo/styles';

import { CHECKOUT_IFRAME_URL } from './constants';
import { useCheckoutPreview } from './context/createContext';
import { initCheckout } from './liveCheckout';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

const CheckoutV2 = ({ shouldScaleToFit }: { shouldScaleToFit: boolean }) => {
  const { isDesktopPreview } = useCheckoutPreview();
  const { values } = useCheckoutEditor();
  const updateCheckout = useRef<Promise<{
    update: (value: Record<string, unknown>) => void;
  }> | null>(null);

  const init = (node: HTMLIFrameElement) => {
    if (node && values && !updateCheckout.current) {
      updateCheckout.current = initCheckout(node, values);
    }
  };

  const updateValues = useMemo(() => {
    return debounce((callback, values) => {
      callback
        .then((result) => {
          result.update(values);
        })
        .catch();
    }, 500);
  }, []);

  useEffect(() => {
    if (values && updateCheckout.current) {
      if (values.logoRaw) {
        toBase64(values.logoRaw).then((base64) => {
          updateValues(updateCheckout.current, {
            ...values,
            logo: base64,
          });
        });
      } else {
        updateValues(updateCheckout.current, values);
      }
    }
  }, [updateValues, values]);

  return (
    <CheckoutFrame
      tabIndex="-1"
      src={CHECKOUT_IFRAME_URL}
      ref={init}
      isDesktopPreview={isDesktopPreview}
      shouldScaleToFit={shouldScaleToFit}
    />
  );
};

export default CheckoutV2;
