import React, { useEffect, useRef, useMemo } from 'react';
import debounce from 'lodash/debounce';

import { useSplitzService } from 'common/splitz';
import { toBase64 } from 'merchant/views/PartnerDashboard/SubMerchant/components/utils';
import { CheckoutFrame } from 'merchant/views/Settings/Configuration/CheckoutDemo/styles';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import { CHECKOUT_IFRAME_URL, CHECKOUT_IFRAME_URL_US } from './constants';
import { useCheckoutPreview } from './context/createContext';
import { initCheckout } from './liveCheckout';
import { getUser } from 'merchant/store';

type CheckoutV2Props = {
  zoomTitleStyle?: boolean;
  forceLoadDesktopView?: boolean;
};

const CheckoutV2 = ({ zoomTitleStyle, forceLoadDesktopView = false }: CheckoutV2Props) => {
  const { isDesktopPreview } = useCheckoutPreview();
  const { values, isLoading } = useCheckoutEditor();

  const merchantAPIKey =
    values[CHECKOUT_EDITOR_FIELDS.API_KEY] || values[CHECKOUT_EDITOR_FIELDS.KEYLESS_HEADER];

  const updateCheckout = useRef<Promise<{
    update: (value: Record<string, unknown>) => void;
  }> | null>(null);

  const merchantKeyRef = useRef<string | null>(null);

  const splitz = useSplitzService();

  const user = getUser();

  const checkoutUrl = useMemo(() => {
    return user.isCountryUS ? CHECKOUT_IFRAME_URL_US : CHECKOUT_IFRAME_URL;
  }, [user]);

  // only change initialisation
  const init = (node: HTMLIFrameElement) => {
    if (!node || !values) return;
    const shouldReInitialize = merchantKeyRef.current !== merchantAPIKey || !updateCheckout.current;
    if (shouldReInitialize) {
      updateCheckout.current = initCheckout(node, values, splitz?.abExperiments);
      merchantKeyRef.current = merchantAPIKey;
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

  if (isLoading) {
    return null;
  }

  return merchantAPIKey ? (
    <CheckoutFrame
      key={merchantAPIKey}
      tabIndex="-1"
      src={checkoutUrl}
      ref={init}
      isDesktopPreview={forceLoadDesktopView || isDesktopPreview}
      zoomTitleStyle={zoomTitleStyle}
    />
  ) : (
    <CheckoutFrame
      tabIndex="-1"
      src={checkoutUrl}
      ref={init}
      isDesktopPreview={forceLoadDesktopView || isDesktopPreview}
      zoomTitleStyle={zoomTitleStyle}
    />
  );
};

export default CheckoutV2;
