export enum BusinessCategory {
    INDIVIDUAL = 'individual',
    NOT_YET_REGISTERED = 'not_yet_registered'
}

const typeIndexMap: Record<BusinessCategory, string> = {
    [BusinessCategory.INDIVIDUAL]: "2",
    [BusinessCategory.NOT_YET_REGISTERED]: "11",
};

export function isBusinessTypeForNotRegisteredBusiness(key: string): boolean {
    return Object.values(typeIndexMap).includes(key);
}
