import React, { useEffect, useRef, useMemo } from 'react';
import debounce from 'lodash/debounce';

import { useSplitzService } from 'common/splitz';
import { toBase64 } from 'merchant/views/PartnerDashboard/SubMerchant/components/utils';
import { CheckoutFrame } from 'merchant/views/Settings/Configuration/CheckoutDemo/styles';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import { CHECKOUT_IFRAME_URL } from './constants';
import { useCheckoutPreview } from './context/createContext';
import { initCheckout } from './liveCheckout';

type CheckoutV2Props = {
  zoomTitleStyle?: boolean;
  forceLoadDesktopView?: boolean;
};

const CheckoutV2 = ({ zoomTitleStyle, forceLoadDesktopView = false }: CheckoutV2Props) => {
  const { isDesktopPreview } = useCheckoutPreview();
  const { values } = useCheckoutEditor();

  const merchantAPIKey = values[CHECKOUT_EDITOR_FIELDS.API_KEY];

  const updateCheckout = useRef<Promise<{
    update: (value: Record<string, unknown>) => void;
  }> | null>(null);

  const splitz = useSplitzService();

  // only change initialisation
  const init = (node: HTMLIFrameElement) => {
    if (node && values && !updateCheckout.current && merchantAPIKey) {
      updateCheckout.current = initCheckout(node, values, splitz?.abExperiments);
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
      }
      if (values.wordmarkRaw) {
        toBase64(values.wordmarkRaw).then((base64) => {
          updateValues(updateCheckout.current, {
            ...values,
            wordmark: base64,
          });
        });
      } else {
        updateValues(updateCheckout.current, values);
      }
    }
  }, [updateValues, values]);

  return merchantAPIKey ? (
    <CheckoutFrame
      tabIndex="-1"
      src={CHECKOUT_IFRAME_URL}
      ref={init}
      isDesktopPreview={forceLoadDesktopView || isDesktopPreview}
      zoomTitleStyle={zoomTitleStyle}
    />
  ) : null;
};

export default CheckoutV2;
