import GenericEntity from './GenericEntity';

export default class Offer extends GenericEntity {
  resourceUrl = 'offers';

  resourceFields = [
    'id',
    'name',
    'description',
    'starts_at',
    'ends_at',
    'type',
    'min_amount',
    'percent_rate',
    'max_cashback',
    'flat_cashback',
    'max_offer_usage',
    'max_payment_count',
    'payment_method',
    'payment_method_type',
    'iins',
    'payment_network',
    'issuer',
    'international',
    'active',
    'block',
    'checkout_display',
    'display_text',
    'emi_subvention',
    'emi_durations',
    'error_message',
    'terms',
    'created_at',
    'current_offer_usage',
    'entity',
    'linked_offer_ids',
  ];

  /**
   * Override the serializeProperty method to perform some operations.
   * @param {String} prop
   * @return {Any}
   */
  serializeProperty(prop) {
    // nothing to serialize
    return super.serializeProperty(prop);
  }

  didDeserialize() {
    // This is used as option display value in the autocomplete
  }
}
