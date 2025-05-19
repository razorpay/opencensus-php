import { merchantFetch } from 'merchant/utils/ajax';
import { useQuery } from '@tanstack/react-query';

type StoreHierarchyItem = {
    store_id?: string;
    name: string;
    type: string;
    parent_group_id: string;
    group_id: string;
};

export type StoreHierarchyResponse = {
    store_hierarchy: StoreHierarchyItem[];
};

const fetchStoreHierarchy = async (): Promise<StoreHierarchyResponse> => {
    const storeHierarchy = await merchantFetch({
        url: 'store_management_service/store_hierarchy',
        data: {},
        method: 'get',
    });
    return storeHierarchy?.data;
};

export const useStoreHierarchy = (showNotification: Function) => {
    return useQuery({
        queryKey: ['store-hierarchy'],
        queryFn: fetchStoreHierarchy,
        onError: () => {
            showNotification({
                type: 'error',
                message: 'Unable to fetch stores information at this moment, please try again later.',
            });
        },
        refetchOnWindowFocus: false,
    });
};
