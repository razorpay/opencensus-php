import { objectDiff, isBlank } from 'rzp/utils/rzp-utils'

/*
  - Model-based approach makes it more handy to handle the serialization/deserialization of crud calls.
  - It decouples those stuffs entirely from the components/actions.
  - Additionally, this takes care of sending only the diff'd JSON for PATCH requests
  - Allows you to create derived properties like `fullName`
  - Allows you to have more semantic resource actions like `invoice.save()`, `invoice.delete()`

  A model should contain the following properties

  1. resourceIdField - the id field for the resource (Default: `id`)
  2. resourceUrl - base url for the resource (eg., `/invoices` for Invoice). This `BaseModel` will take of appending the resourceIdField while fetching a single resource like (`/invoices/inv_asndj12312k3jnnl`)
  3. resourceFields - array of property names to be sent as JSON payload (serialization)
*/

export default class BaseModel {
  resourceIdField = 'id'

  constructor(props = {}) {
    Object.assign(this, props)
    this.stashPayload(props)
  }

  get isNew() {
    return !this[this.resourceIdField]
  }

  getResourceUrl() {
    if (this.isNew) {
      return this.resourceUrl
    }
    return `${this.resourceUrl}/${this[this.resourceIdField]}`
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'patch'
  }

  getResourceUrlAndMethod() {
    return [ this.getResourceUrl() , this.getResourceMethod() ]
  }

  /*
    Returns JSON payload with only properties specified in `resourceFields`
  */
  serialize() {
    let fields = (typeof this.resourceFields === 'function') ? this.resourceFields() : this.resourceFields
    let serializedModel = {}

    for (let i = 0, len = fields.length; i < len; i++) {
      let prop = fields[i]
      serializedModel[prop] = this.serializeProperty(prop)
    }

    if (this.getResourceMethod() === 'put') {
      return serializedModel;
    }

    return objectDiff(this.__stashed__, serializedModel)
  }

  /*
    Hook to handle your own serialization logic.
    Make sure you return `super.serializeProperty(prop)` on the overriding method
   */
  serializeProperty(prop) {
    return this[prop]
  }

  /*
    Converts the raw JSON payload to a Model
   */
  deserialize(json) {
    for (let prop in json) {
      this.deserializeProperty(prop, json[prop])
    }

    this.didDeserialize()
    this.stashPayload(json)
    return this
  }

  /*
    Hook to handle your own deserialization logic.
    Make sure you return `super.deserializeProperty(prop, value)` on the overriding method
   */
  deserializeProperty(prop, value) {
    this[prop] = value
  }

  stashPayload(json) {
    if (!this.isNew && isBlank(this.__stashed__)) {
      this.__stashed__ = json
    }
  }

  /*
    Hook to that gets invoked after the deserialization is done.
  */
  didDeserialize() {}

  toString() {
    return 'model'
  }
}
