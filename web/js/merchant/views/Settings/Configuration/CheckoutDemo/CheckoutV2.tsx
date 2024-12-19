import React, { useEffect, useRef, useMemo } from 'react';
import debounce from 'lodash/debounce';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';

import { useSplitzService } from 'common/splitz';

import { Environments } from 'common/typings';
import { fetchKeys } from 'merchant/reducers/keys';
import { toBase64 } from 'merchant/views/PartnerDashboard/SubMerchant/components/utils';
import { CheckoutFrame } from 'merchant/views/Settings/Configuration/CheckoutDemo/styles';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import { CHECKOUT_IFRAME_URL } from './constants';
import { useCheckoutPreview } from './context/createContext';
import { initCheckout } from './liveCheckout';

type CheckoutV2Props = {
  zoomTitleStyle?: boolean;
  fetchKeys: typeof fetchKeys;
  apiKey: string;
  mode: Environments;
  forceLoadDesktopView?: boolean;
};

const CheckoutV2 = ({
  zoomTitleStyle,
  fetchKeys,
  apiKey,
  mode,
  forceLoadDesktopView = false,
}: CheckoutV2Props) => {
  const { isDesktopPreview } = useCheckoutPreview();
  const { values } = useCheckoutEditor();
  const updateCheckout = useRef<Promise<{
    update: (value: Record<string, unknown>) => void;
  }> | null>(null);

  const splitz = useSplitzService();

  const init = (node: HTMLIFrameElement) => {
    if (node && values && !updateCheckout.current) {
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

  useEffect(() => {
    if (apiKey === undefined) {
      fetchKeys({ mode });
    }
  }, [mode, fetchKeys, apiKey]);

  return (
    <CheckoutFrame
      tabIndex="-1"
      src={`${CHECKOUT_IFRAME_URL}&key=${apiKey}`}
      ref={init}
      isDesktopPreview={forceLoadDesktopView || isDesktopPreview}
      zoomTitleStyle={zoomTitleStyle}
    />
  );
};

const mapActionsToProps = (dispatch: Dispatch<AnyAction>) => {
  return bindActionCreators(
    {
      fetchKeys,
    },
    dispatch,
  );
};

export default connect((state) => {
  return {
    user: state.session.user,
    apiKey: state.keys?.keys?.[0]?.id ?? null,
    mode: state.session.mode,
  };
}, mapActionsToProps)(CheckoutV2);
