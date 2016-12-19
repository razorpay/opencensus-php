export default class BaseModel {
  resourceIdField = 'id'

  constructor(props) {
    Object.assign(this, props)
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
    return this.isNew ? 'post' : 'put'
  }

  getResourceUrlAndMethod() {
    return [ this.getResourceUrl() , this.getResourceMethod() ]
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
    this.didDeserialize()
    return this
  }

  deserializeProperty(prop, value) {
    this[prop] = value
  }

  didDeserialize() {}

  toString() {
    return 'model'
  }
}
