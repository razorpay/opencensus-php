import { StoreHierarchyResponse } from '../services/storesService';

type TreeNode = {
    label: string;
    value: string;
    children: TreeNode[];
};

export const transformToTreeData = (data: StoreHierarchyResponse['store_hierarchy']): TreeNode[] => {
    const idMap: Record<string, TreeNode> = {};
    const root: TreeNode[] = [];

    data.forEach(item => {
        const node: TreeNode = {
            label: item.name,
            value: item.store_id || item.group_id,
            children: []
        };
        idMap[item.group_id] = node;
    });

    data.forEach(item => {
        const node = idMap[item.group_id];
        const parent = item.parent_group_id ? idMap[item.parent_group_id] : null;
        if (parent) {
            parent.children.push(node);
        } else {
            root.push(node);
        }
    });

    return root;
};
