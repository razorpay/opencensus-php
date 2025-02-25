/**
 * Returns the applicable GST groups, a corresponding mapping, and the rate per GST group.
 * 
 * @example
 * const slab = 50000;
 * const serviceStateCode = 'KA';
 * const supplyStateCode = 'KA';
 * const mapping = { 'CGST_25000': 'TaxID1', 'SGST_25000': 'TaxID2' };
 * const isUT = false;
 * const result = getApplicableGSTForSlab(slab, serviceStateCode, supplyStateCode, mapping, isUT);
 * console.log(result);
 * // Output: {
 * //   groups: ['CGST', 'SGST'],
 * //   mapping: { 'CGST_25000': 'TaxID1', 'SGST_25000': 'TaxID2' },
 * //   perGroup: 25000
 * // }
 * 
 * @param {number} slab - Slab, e.g., 50000 (5%, value is multiple of 10000).
 * @param {string} serviceStateCode - State Code of Service (Merchant).
 * @param {string} supplyStateCode - State Code of Supply (Customer).
 * @param {Record<string, string>} mapping - TaxGroup_Slab => Razorpay_Tax_ID mapping.
 * @param {boolean} isUT - Whether or not any of the states is a Union Territory.
 * @returns {Object} - Contains the applicable GST groups, corresponding mapping, and the rate per GST group.
 * @property {string[]} groups - List of applicable groups.
 * @property {Record<string, string>} mapping - The mapping that was passed, but only the applicable ones.
 * @property {number} perGroup - Percentage rate per group.
 */
export const getApplicableGSTForSlab = (
  slab: number,
  serviceStateCode: string|number,
  supplyStateCode: string|number,
  mapping: Record<string, string>,
  isUT: boolean
): { groups: string[]; mapping: Record<string, string>; perGroup: number } => {
  let mapKeys = Object.keys(mapping);

  /**
   * For different states, it is IGST.
   * For same state, if it is a Union Territory, it is CGST+UTGST.
   * For same state, if it is not a Union Territory, it is CGST+SGST.
   */
  let groups = ['IGST'];
  if (serviceStateCode === supplyStateCode) {
    groups = isUT ? ['CGST', 'UTGST'] : ['CGST', 'SGST'];
  }

  let applicable: Record<string, string> = {};
  let perKey = slab / groups.length;
  for (let i = 0; i < mapKeys.length; i++) {
    let key = mapKeys[i];
    for (let j = 0; j < groups.length; j++) {
      let group = groups[j];
      if (`${group}_${perKey}` === key) {
        applicable[key] = mapping[key];
      }
    }
  }

  return {
    groups,
    mapping: applicable,
    perGroup: perKey,
  };
};
