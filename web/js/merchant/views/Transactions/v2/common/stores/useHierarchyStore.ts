import create from 'zustand';

type TreeNode = {
    label: string;
    value: string;
    children: TreeNode[];
};

type StoresState = {
    flatStores: string[];
    setFlatStores: (stores: string[]) => void;
    stores: TreeNode[];
    setStores: (stores: TreeNode[]) => void;
};

export const useHierarchyStore = create<StoresState>((set) => ({
    flatStores: [],
    setFlatStores: (flatStores) => set({ flatStores }),
    stores: [],
    setStores: (stores) => set({ stores }),
}));
