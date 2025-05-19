import React, { useEffect } from 'react';
import { useStoreHierarchy } from 'merchant/views/Transactions/v2/common/services/storesService';
import { transformToTreeData } from 'merchant/views/Transactions/v2/common/utils/storeUtils';
import { useHierarchyStore } from 'merchant/views/Transactions/v2/common/stores/useHierarchyStore';
import { getUser } from '@federated/apps/shell/commonStore';
import { isOmniChannelMerchant } from 'merchant/utils/omniUtils';
import { isOmniHomepageEnabled } from 'merchant/containers/Home/RTUX/utils';
import { useSplitzService } from 'common/splitz';

const useFetchStores = (showNotification: (msg: string) => void) => {
  const user = getUser();
  const splitz = useSplitzService();
  const isOmniMerchant = isOmniChannelMerchant(user);
  const isStoreHierarchyEnabled = isOmniHomepageEnabled(splitz?.abExperiments);
  if (!isOmniMerchant || !isStoreHierarchyEnabled) {
    return null;
  }
  const setStores = useHierarchyStore((state) => state.setStores);
  const setFlatStores = useHierarchyStore((state) => state.setFlatStores);
  const stores = useStoreHierarchy((state) => state.stores);

  const { data: storeHierarchy } = useStoreHierarchy(showNotification);
  
  useEffect(() => {
    if (storeHierarchy?.store_hierarchy) {
      const treeData = transformToTreeData(storeHierarchy.store_hierarchy);
      setFlatStores(storeHierarchy.store_hierarchy);
      setStores(treeData);
    }
  }, [stores]);
};

export default useFetchStores;