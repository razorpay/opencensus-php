import { objectDiff, isBlank } from 'rzp/utils/rzp-utils'

export default class BaseModel {
  static resourceIdField = 'id'

  constructor(props = {}) {
    Object.assign(this, props)
    this.stashPayload(props)
  }

  get isNew() {
    const Klass = this.constructor
    return !this[Klass.resourceIdField]
  }

  getResourceUrl() {
    const Klass = this.constructor
    if (this.isNew) {
      return Klass.resourceUrl
    }
    return `${Klass.resourceUrl}/${this[Klass.resourceIdField]}`
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'patch'
  }

  getResourceUrlAndMethod() {
    return [ this.getResourceUrl() , this.getResourceMethod() ]
  }

  serialize() {
    let fields = (typeof this.resourceFields === 'function') ? this.resourceFields() : this.resourceFields
    let serializedModel = {}

    for (let i = 0, len = fields.length; i < len; i++) {
      let prop = fields[i]
      serializedModel[prop] = this.serializeProperty(prop)
    }

    return objectDiff(this.__stashed__, serializedModel)
  }

  serializeProperty(prop) {
    return this[prop]
  }

  deserialize(json) {
    for (let prop in json) {
      this.deserializeProperty(prop, json[prop])
    }

    this.didDeserialize()
    this.stashPayload(json)
    return this
  }

  deserializeProperty(prop, value) {
    this[prop] = value
  }

  stashPayload(json) {
    if (!this.isNew && isBlank(this.__stashed__)) {
      this.__stashed__ = json
    }
  }

  didDeserialize() {}

  toString() {
    return 'model'
  }
}
