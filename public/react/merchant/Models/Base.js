export default class BaseModel {
  static resourceIdField = 'id'

  constructor(props) {
    Object.assign(this, props)
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
    return this.isNew ? 'post' : 'put'
  }

  getResourceUrlAndMethod() {
    return [ this.getResourceUrl() , this.getResourceMethod() ]
  }

  serialize() {
    let Klass = this.constructor
    let resourceProperties = Klass.resourceProperties
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
