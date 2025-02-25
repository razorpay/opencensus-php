import { getApplicableGSTForSlab } from "./getApplicableGSTForSlab";

/**
 * Returns the applicable GST groups, a corresponding mapping, and the rate per GST group for given slabs.
 * @param {number[]} slabs - Array of slab amounts, e.g., [0, 500, 1200, ...].
 * @param {number} serviceStateCode - The state code of the service (merchant).
 * @param {number} supplyStateCode - The state code of the supply (customer).
 * @param {Record<string, string>} mapping - A mapping object, TaxGroup_Slab => Razorpay_Tax_ID.
 * @param {boolean} isUT - Whether the state is a Union Territory.
 * @returns {Record<number, { groups: string[]; mapping: Record<string, string>; perGroup: number }>} 
 * - Object with slabs as keys, each containing applicable GST groups, mapping, and rate per group.
 *
 * @example
 * const slabs = [500, 1200];
 * const serviceStateCode = 27;
 * const supplyStateCode = 27;
 * const mapping = { "CGST_5": "tax1", "SGST_5": "tax2" };
 * const isUT = false;
 * const result = getGSTSlabs(slabs, serviceStateCode, supplyStateCode, mapping, isUT);
 * console.log(result);
 * // Output: { 500: { groups: ['CGST', 'SGST'], mapping: { "CGST_5": "tax1", "SGST_5": "tax2" }, perGroup: 250 } }
 */
export const getGSTSlabs = (
  slabs: number[],
  serviceStateCode: number,
  supplyStateCode: number,
  mapping: Record<string, string>,
  isUT: boolean
): Record<number, { groups: string[]; mapping: Record<string, string>; perGroup: number }> => {
  let toReturn: Record<number, { groups: string[]; mapping: Record<string, string>; perGroup: number }> = {};
  slabs.forEach((slab) => {
    toReturn[slab] = getApplicableGSTForSlab(
      slab,
      serviceStateCode,
      supplyStateCode,
      mapping,
      isUT
    );
  });
  return toReturn;
};
