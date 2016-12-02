export default class BaseModel {
  resourceIdField = 'id'

  constructor(props = {}) {
    Object.assign(this, props)
  }

  get isNew() {
    return !this[this.resourceIdField]
  }

  getResourceUrlAndMethod() {
    if (this[this.resourceIdField]) {
      return [ `${this.resourceUrl}/${this.resourceIdField}`, 'put' ]
    }

    return [ this.resourceUrl, 'post' ]
  }

  serialize() {
    let resourceProperties = this.resourceProperties
    let serializedModel = {}

    for (let i = 0, len = resourceProperties.length; i < len; i++) {
      let prop = resourceProperties[i]
      serializedModel[prop] = this.serializeProperty(prop)
    }

    return serializedModel
  }

  serializeProperty(prop) {
    return this[prop]
  }

  deserialize(json) {
    for (let prop in json) {
      this.deserializeProperty(prop, json[prop])
    }
    return this
  }

  deserializeProperty(prop, value) {
    this[prop] = value
  }
}
