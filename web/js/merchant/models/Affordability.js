import GenericEntity from './GenericEntity';

export default class AffordabilityWidget extends GenericEntity {
  resourceUrl = 'affordability/widget/details';

  serializeProperty(prop) {
    // nothing to serialize
    return super.serializeProperty(prop);
  }

  didDeserialize() {
    // This is used as option display value in the autocomplete
  }
}
