import React, { Suspense } from 'react';

import { useApp } from 'common/context/App';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { getItem, setItem } from 'common/utils/localStorage';
import lazyLoader from 'merchant/routes/LazyLoader';

const FestiveAnimation = lazyLoader(() =>
  import(/* webpackChunkName: 'festiveAnimation' */ './FestiveAnimation'),
);

const CONFIG_KEY = 'festive_animation_views';
const MERCHANT_VIEW_KEY = 'view';
const VIEW_LIMIT = 5;

const getViewConfig = ({ userId }) => {
  if (!userId) {
    return false;
  }
  const config = JSON.parse(getItem(CONFIG_KEY)) || {};
  const merchantConfig = config?.[`${MERCHANT_VIEW_KEY}_${userId}`] || 0;
  return parseInt(merchantConfig, 10) <= VIEW_LIMIT;
};

const setViewConfig = ({ userId }) => {
  if (userId) {
    const config = JSON.parse(getItem(CONFIG_KEY)) || {};
    const merchantKey = `${MERCHANT_VIEW_KEY}_${userId}`;
    const merchantConfig = parseInt(config?.[merchantKey] || 0, 10);
    const updatedConfig = {
      ...config,
      [merchantKey]: merchantConfig + 1,
    };
    setItem(CONFIG_KEY, JSON.stringify(updatedConfig));
  }
};

const FestiveAnimationWrapper = ({ isMobile, user }) => {
  const { isShowFestiveAnimation, handleFestiveAnimeAction } = useApp();
  const {
    abExperiments: { Festive_Anime } = {},
  } = useSplitzService();
  const isFestiveAnimeExperimentEnabled = isExperimentEnabled(Festive_Anime);

  const isViewsPending = getViewConfig({ userId: user?.id });

  const isRenderFestiveAnimation =
    Boolean(isShowFestiveAnimation) && isFestiveAnimeExperimentEnabled && isViewsPending;

  if (!isRenderFestiveAnimation) {
    return null;
  }

  return (
    <Suspense>
      <FestiveAnimation
        isMobile={isMobile}
        handleClose={Boolean(handleFestiveAnimeAction) ? handleFestiveAnimeAction : () => {}}
        isShow={Boolean(isShowFestiveAnimation)}
        updateConfig={() => setViewConfig({ userId: user?.id })}
      />
    </Suspense>
  );
};

export default FestiveAnimationWrapper;
