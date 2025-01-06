export type StoreType = {
  id: string;
  name: string;
  storeInfo: {
    storeCode: string;
  };
};

export type StoreGroup = {
  id: string;
  name: string;
  description: string | null;
  isActive: boolean | null;
  stores: StoreType[];
};

export type StoreGroupsDataResponse = {
  storeGroups: {
    storeGroups: StoreGroup[];
    limit: number;
    offset: number;
    total: number;
  };
};

export type StoreGroupResponse = {
  storeGroupById: StoreGroup;
};

export type StoreGroupForListing = Omit<StoreGroup, 'stores'> & { storesCount: number };

export type StoreGroupModalStatus = 'create' | 'update' | 'delete' | null;

export type StoreChipType = {
  label: string;
  value: string;
};

export type StoreGroupInfoType = {
  name: string;
  description: string | null;
  stores: {
    [key: string]: StoreChipType;
  };
};

export type ModifiedFieldsMapType = {
  [K in keyof StoreGroupInfoType]?: boolean;
};
